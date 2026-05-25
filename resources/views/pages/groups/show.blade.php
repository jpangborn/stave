<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\GroupMembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use App\Notifications\GroupJoinRequestNotification;
use App\Notifications\GroupMemberAddedNotification;
use App\Notifications\GroupMembershipResponseNotification;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    public Group $group;

    #[Url]
    public string $tab = 'conversations';

    public string $memberSearch = '';

    public string $searchConvos = '';

    public string $searchMembers = '';

    public string $memberFilter = 'all';

    public ?int $confirmDeleteId = null;

    public bool $addMemberOpen = false;

    /** @var array<int, int> */
    public array $pickedUserIds = [];

    public function mount(Group $group): void
    {
        $this->authorize('view', $group);
        $this->group = $group;

        if (! $this->conversationsVisible) {
            $this->tab = 'members';
        }
    }

    #[Computed]
    public function membership(): ?GroupUser
    {
        /** @var ?GroupUser */
        return $this->group->allUsers()->where('user_id', Auth::id())->first()?->pivot;
    }

    #[Computed]
    public function isMember(): bool
    {
        return $this->membership?->status === GroupMembershipStatus::ACTIVE;
    }

    #[Computed]
    public function isLeader(): bool
    {
        return $this->group->leaders()->where('user_id', Auth::id())->exists();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function pendingRequests(): Collection
    {
        return $this->group->pendingRequests()->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function activeMembers(): Collection
    {
        return $this->group->members()->get();
    }

    #[Computed]
    public function leaderCount(): int
    {
        return $this->activeMembers->filter(
            fn (User $u): bool => $this->pivotRole($u) === GroupRole::LEADER,
        )->count();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function filteredMembers(): Collection
    {
        $needle = mb_strtolower(trim($this->searchMembers));

        return $this->activeMembers
            ->when($this->memberFilter !== 'all', fn ($c) => $c->filter(
                fn (User $u): bool => $this->pivotRole($u)->value === $this->memberFilter,
            ))
            ->when($needle !== '', fn ($c) => $c->filter(
                fn (User $u): bool => str_contains(mb_strtolower($u->name), $needle)
                    || str_contains(mb_strtolower($u->email), $needle),
            ))
            ->values();
    }

    private function pivotRole(User $user): GroupRole
    {
        /** @var GroupUser $pivot */
        $pivot = $user->getRelation('pivot');

        return $pivot->role;
    }

    #[Computed]
    public function conversationsVisible(): bool
    {
        return $this->isMember && $this->group->messaging !== GroupMessaging::OFF;
    }

    /** @return Collection<int, Conversation> */
    #[Computed]
    public function conversations(): Collection
    {
        return $this->group->conversations()
            ->with(['creator', 'firstComment'])
            ->withCount('comments')
            ->orderByRaw('CASE WHEN pinned_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('pinned_at')
            ->orderByRaw('COALESCE(last_comment_at, created_at) desc')
            ->get();
    }

    /** @return Collection<int, Conversation> */
    #[Computed]
    public function filteredConversations(): Collection
    {
        $needle = mb_strtolower(trim($this->searchConvos));

        if ($needle === '') {
            return $this->conversations;
        }

        return $this->conversations
            ->filter(fn (Conversation $c) => str_contains(mb_strtolower($c->title), $needle))
            ->values();
    }

    /** @return Collection<int, User>|BaseCollection<int, User> */
    #[Computed]
    public function availableUsers(): Collection|BaseCollection
    {
        if (strlen($this->memberSearch) < 2) {
            return collect();
        }

        $existingUserIds = $this->group->allUsers()
            ->whereIn('group_user.status', [GroupMembershipStatus::ACTIVE, GroupMembershipStatus::PENDING])
            ->pluck('users.id');

        return User::query()
            ->where(function ($q): void {
                $q->where('name', 'like', "%{$this->memberSearch}%")
                    ->orWhere('email', 'like', "%{$this->memberSearch}%");
            })
            ->whereNotIn('id', $existingUserIds)
            ->whereNotIn('id', $this->pickedUserIds)
            ->limit(8)
            ->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function pickedUsers(): Collection
    {
        if ($this->pickedUserIds === []) {
            return new Collection;
        }

        return User::query()->whereIn('id', $this->pickedUserIds)->get();
    }

    public function openAddMember(): void
    {
        $this->addMemberOpen = true;
    }

    public function closeAddMember(): void
    {
        $this->addMemberOpen = false;
        $this->pickedUserIds = [];
        $this->memberSearch = '';
        unset($this->pickedUsers, $this->availableUsers);
    }

    public function pickUser(int $userId): void
    {
        if (! in_array($userId, $this->pickedUserIds, true)) {
            $this->pickedUserIds[] = $userId;
        }
        $this->memberSearch = '';
        unset($this->pickedUsers, $this->availableUsers);
    }

    public function unpickUser(int $userId): void
    {
        $this->pickedUserIds = array_values(array_filter(
            $this->pickedUserIds,
            fn (int $id) => $id !== $userId,
        ));
        unset($this->pickedUsers, $this->availableUsers);
    }

    public function confirmAddMembers(): void
    {
        if ($this->pickedUserIds !== []) {
            $this->addMembers($this->pickedUserIds);
        }
        $this->closeAddMember();
    }

    public function join(): void
    {
        $this->authorize('join', $this->group);

        $existing = $this->group->allUsers()->where('user_id', Auth::id())->exists();

        if ($existing) {
            $this->group->allUsers()->updateExistingPivot(Auth::id(), [
                'status' => GroupMembershipStatus::PENDING,
            ]);
        } else {
            $this->group->allUsers()->attach(Auth::id(), [
                'role' => GroupRole::MEMBER,
                'status' => GroupMembershipStatus::PENDING,
            ]);
        }

        $this->notifyLeaders();

        unset($this->membership);
        Flux::toast(text: 'Join request sent.');
    }

    public function cancelRequest(): void
    {
        /** @var ?GroupUser $membership */
        $membership = $this->group->allUsers()->where('user_id', Auth::id())->first()?->pivot;

        if ($membership?->status !== GroupMembershipStatus::PENDING) {
            return;
        }

        $this->group->allUsers()->detach(Auth::id());

        unset($this->membership);
        Flux::toast(text: 'Join request cancelled.');
    }

    public function leave(): void
    {
        $this->authorize('leave', $this->group);

        DB::transaction(function (): void {
            $member = $this->group->members()
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (! $member) {
                return;
            }

            if ($member->pivot->role === GroupRole::LEADER
                && $this->group->leaders()->lockForUpdate()->count() === 1) {
                Flux::toast(variant: 'danger', text: 'Cannot leave as the only leader.');

                return;
            }

            $this->group->allUsers()->detach(Auth::id());
        });

        unset($this->membership, $this->isLeader, $this->activeMembers);
        Flux::toast(text: 'You left the group.');
    }

    public function approveMember(int $userId): void
    {
        $this->authorize('manageMembers', $this->group);

        $user = DB::transaction(function () use ($userId): ?User {
            $user = $this->group->pendingRequests()
                ->whereKey($userId)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            $this->group->allUsers()->updateExistingPivot($user->id, [
                'status' => GroupMembershipStatus::ACTIVE,
            ]);

            return $user;
        });

        if (! $user) {
            return;
        }

        $user->notify(new GroupMembershipResponseNotification($this->group, approved: true));

        unset($this->pendingRequests, $this->activeMembers);
        Flux::toast(text: "{$user->name} approved.");
    }

    public function rejectMember(int $userId): void
    {
        $this->authorize('manageMembers', $this->group);

        $user = DB::transaction(function () use ($userId): ?User {
            $user = $this->group->pendingRequests()
                ->whereKey($userId)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            $this->group->allUsers()->updateExistingPivot($user->id, [
                'status' => GroupMembershipStatus::REJECTED,
            ]);

            return $user;
        });

        if (! $user) {
            return;
        }

        $user->notify(new GroupMembershipResponseNotification($this->group, approved: false));

        unset($this->pendingRequests);
        Flux::toast(text: "{$user->name} rejected.");
    }

    /** @param  array<int, int|string>  $userIds */
    public function addMembers(array $userIds): void
    {
        $this->authorize('manageMembers', $this->group);

        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if ($userIds === []) {
            return;
        }

        $existingIds = $this->group->allUsers()
            ->whereIn('users.id', $userIds)
            ->pluck('users.id')
            ->all();

        $users = User::query()->whereIn('id', $userIds)->get();

        DB::transaction(function () use ($users, $existingIds): void {
            foreach ($users as $user) {
                if (in_array($user->id, $existingIds, true)) {
                    $this->group->allUsers()->updateExistingPivot($user->id, [
                        'status' => GroupMembershipStatus::ACTIVE,
                    ]);
                } else {
                    $this->group->allUsers()->attach($user->id, [
                        'role' => GroupRole::MEMBER,
                        'status' => GroupMembershipStatus::ACTIVE,
                    ]);
                }
            }
        });

        foreach ($users as $user) {
            $user->notify(new GroupMemberAddedNotification($this->group, Auth::user()));
        }

        $this->reset('memberSearch');
        unset($this->activeMembers, $this->pendingRequests, $this->availableUsers);

        $count = $users->count();
        Flux::toast(text: $count === 1
            ? "{$users->first()->name} added to group."
            : "{$count} members added to group.");
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('manageMembers', $this->group);

        DB::transaction(function () use ($userId): void {
            $user = $this->group->members()
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->pivot->role === GroupRole::LEADER
                && $this->group->leaders()->lockForUpdate()->count() === 1) {
                Flux::toast(variant: 'danger', text: 'Cannot remove the only leader.');

                return;
            }

            $this->group->allUsers()->detach($user->id);

            unset($this->activeMembers);
            Flux::toast(text: "{$user->name} removed from group.");
        });
    }

    public function setMemberRole(int $userId, string $role): void
    {
        $this->authorize('manageMembers', $this->group);

        $targetRole = GroupRole::tryFrom($role);
        if (! $targetRole) {
            return;
        }

        DB::transaction(function () use ($userId, $targetRole): void {
            $user = $this->group->members()
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->pivot->role === $targetRole) {
                return;
            }

            if ($user->pivot->role === GroupRole::LEADER
                && $targetRole === GroupRole::MEMBER
                && $this->group->leaders()->lockForUpdate()->count() === 1) {
                Flux::toast(variant: 'danger', text: 'A group must have at least one leader.');

                return;
            }

            $this->group->allUsers()->updateExistingPivot($user->id, [
                'role' => $targetRole,
            ]);

            unset($this->activeMembers);
            Flux::toast(text: $targetRole === GroupRole::LEADER
                ? "{$user->name} promoted to leader."
                : "{$user->name} changed to member.");
        });
    }

    public function pinConversation(int $conversationId): void
    {
        $conversation = $this->group->conversations()->whereKey($conversationId)->firstOrFail();
        $this->authorize('pin', $conversation);

        $conversation->pin(Auth::user());

        unset($this->conversations);
        Flux::toast(text: 'Conversation pinned.');
    }

    public function unpinConversation(int $conversationId): void
    {
        $conversation = $this->group->conversations()->whereKey($conversationId)->firstOrFail();
        $this->authorize('pin', $conversation);

        $conversation->unpin();

        unset($this->conversations);
        Flux::toast(text: 'Conversation unpinned.');
    }

    public function openDeleteConversation(int $conversationId): void
    {
        $this->confirmDeleteId = $conversationId;
        $this->modal('delete-conversation')->show();
    }

    public function deleteConversation(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $conversation = $this->group->conversations()->whereKey($this->confirmDeleteId)->firstOrFail();
        $this->authorize('delete', $conversation);

        $conversation->delete();

        $this->confirmDeleteId = null;
        $this->modal('delete-conversation')->close();

        unset($this->conversations);
        Flux::toast(text: 'Conversation deleted.');
    }

    public function previewFor(Conversation $conversation): string
    {
        /** @var ?\App\Models\Comment $first */
        $first = $conversation->firstComment->first();

        if (! $first) {
            return '';
        }

        $text = trim(html_entity_decode(strip_tags((string) $first->text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return Str::limit($text, 100);
    }

    public function previewAuthorFor(Conversation $conversation): string
    {
        /** @var ?\App\Models\Comment $first */
        $first = $conversation->firstComment->first();
        $commentator = $first?->commentator;

        if ($commentator instanceof User) {
            return $commentator->name;
        }

        return $conversation->creator->name ?? 'Unknown';
    }

    private function notifyLeaders(): void
    {
        Notification::send(
            $this->group->leaders,
            new GroupJoinRequestNotification($this->group, Auth::user()),
        );
    }
};
?>

<section class="w-full">
    <div class="flex items-start justify-between gap-4">
        <div class="flex items-start gap-4">
            @if ($group->image)
                <img src="{{ Storage::disk('digital-ocean')->url($group->image) }}" alt="{{ $group->name }}" class="size-24 rounded-lg object-cover" />
            @endif

            <div>
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 mb-1">
                    <a href="{{ route('groups.index') }}" wire:navigate class="hover:text-zinc-700 dark:hover:text-zinc-200">Groups</a>
                    <flux:icon.chevron-right class="size-3" />
                    <span>{{ $group->name }}</span>
                </div>

                <flux:heading size="xl" level="1">{{ $group->name }}</flux:heading>

                <flux:subheading size="lg" class="mt-2">
                    <flux:badge size="sm" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
                    <flux:badge size="sm" :color="$group->messaging->color()">{{ $group->messaging->label() }}</flux:badge>
                    <span class="ml-2">{{ $this->activeMembers->count() }} {{ str('member')->plural($this->activeMembers->count()) }}</span>
                </flux:subheading>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $group)
                <flux:button :href="route('groups.edit', $group)" variant="ghost" icon="pencil-square" wire:navigate>Edit group</flux:button>
            @endcan

            @if ($this->membership === null && $group->visibility === GroupVisibility::PUBLIC)
                <flux:button wire:click="join" variant="primary" icon="user-plus">Request to Join</flux:button>
            @elseif ($this->membership?->status === GroupMembershipStatus::PENDING)
                <div class="flex items-center gap-2">
                    <flux:badge color="amber">Request Pending</flux:badge>
                    <flux:button wire:click="cancelRequest" variant="ghost" size="sm">Cancel</flux:button>
                </div>
            @elseif ($this->membership?->status === GroupMembershipStatus::ACTIVE && ! $this->isLeader)
                <flux:button wire:click="leave" variant="danger" icon="arrow-right-start-on-rectangle"
                    wire:confirm="Are you sure you want to leave this group?">Leave Group</flux:button>
            @elseif ($this->membership?->status === GroupMembershipStatus::REJECTED)
                <flux:button wire:click="join" variant="primary" icon="user-plus">Request to Join</flux:button>
            @endif
        </div>
    </div>

    @if ($group->description)
        <div class="prose prose-sm dark:prose-invert mt-4 max-w-none">
            {!! $group->description !!}
        </div>
    @endif

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model.live="tab">
            @if ($this->conversationsVisible)
                <flux:tab name="conversations" icon="chat-bubble-left-right">
                    Conversations
                    <span class="ml-1 inline-flex items-center justify-center rounded-full px-1.5 py-px text-[11px] font-semibold {{ $tab === 'conversations' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                        {{ $this->conversations->count() }}
                    </span>
                </flux:tab>
            @endif
            @if ($this->isMember)
                <flux:tab name="members" icon="user-group">
                    Members
                    <span class="ml-1 inline-flex items-center justify-center rounded-full px-1.5 py-px text-[11px] font-semibold {{ $tab === 'members' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                        {{ $this->activeMembers->count() }}
                    </span>
                    @if ($this->isLeader && $this->pendingRequests->isNotEmpty())
                        <flux:badge size="sm" color="amber" class="ml-1">{{ $this->pendingRequests->count() }}</flux:badge>
                    @endif
                </flux:tab>
            @endif
        </flux:tabs>

        @if ($this->conversationsVisible)
            <flux:tab.panel name="conversations">
                <div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
                    <flux:input
                        wire:model.live.debounce.300ms="searchConvos"
                        icon="magnifying-glass"
                        placeholder="Search conversations…"
                        class="!w-full sm:!w-[300px]"
                    />

                    @can('create', [Conversation::class, $group])
                        <flux:button :href="route('groups.conversations.create', $group)" variant="primary" icon="plus" wire:navigate>
                            Start Conversation
                        </flux:button>
                    @endcan
                </div>

                @php
                    $pinned = $this->filteredConversations->filter(fn (Conversation $c) => $c->isPinned())->values();
                    $rest = $this->filteredConversations->filter(fn (Conversation $c) => ! $c->isPinned())->values();
                @endphp

                @if ($this->filteredConversations->isEmpty())
                    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 py-12 px-6 text-center">
                        <div class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                            <flux:icon.chat-bubble-left-right class="size-5" />
                        </div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $this->searchConvos !== '' ? 'No conversations match.' : 'No conversations yet' }}
                        </div>
                        @if ($this->searchConvos === '')
                            <div class="mt-1 text-[13px] text-zinc-500 dark:text-zinc-400">
                                Start a thread to get the discussion going.
                            </div>
                        @endif
                    </div>
                @else
                    @if ($pinned->isNotEmpty())
                        <div class="mb-5">
                            <div class="flex items-center gap-1.5 px-1 pb-2 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                <flux:icon.bookmark class="size-3" />
                                Pinned
                            </div>

                            <div class="flex flex-col">
                                @foreach ($pinned as $conversation)
                                    @include('pages.groups._partials.conversation-row', [
                                        'conversation' => $conversation,
                                        'group' => $group,
                                        'isLeader' => $this->isLeader,
                                        'preview' => $this->previewFor($conversation),
                                        'previewAuthor' => $this->previewAuthorFor($conversation),
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($rest->isNotEmpty())
                        <div>
                            @if ($pinned->isNotEmpty())
                                <div class="px-1 pb-2 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                    All conversations
                                </div>
                            @endif

                            <div class="flex flex-col">
                                @foreach ($rest as $conversation)
                                    @include('pages.groups._partials.conversation-row', [
                                        'conversation' => $conversation,
                                        'group' => $group,
                                        'isLeader' => $this->isLeader,
                                        'preview' => $this->previewFor($conversation),
                                        'previewAuthor' => $this->previewAuthorFor($conversation),
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                @if ($this->isLeader)
                <flux:modal name="delete-conversation" class="min-w-[26rem]">
                    @php
                        $deleting = $this->confirmDeleteId
                            ? $this->conversations->firstWhere('id', $this->confirmDeleteId)
                            : null;
                    @endphp

                    <form wire:submit="deleteConversation" class="space-y-5">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300">
                                <flux:icon.trash class="size-5" />
                            </div>
                            <flux:heading size="lg">Delete this conversation?</flux:heading>
                            <flux:subheading class="mt-2">
                                @if ($deleting)
                                    <span class="font-semibold text-zinc-700 dark:text-zinc-200">"{{ $deleting->title }}"</span>
                                    and all
                                    {{ $deleting->comments_count }}
                                    {{ str('reply')->plural($deleting->comments_count) }}
                                    will be permanently removed. This cannot be undone.
                                @endif
                            </flux:subheading>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-zinc-200 dark:border-zinc-700 -mx-6 -mb-6 px-6 py-4 bg-zinc-50 dark:bg-zinc-900/40 rounded-b-lg">
                            <flux:modal.close>
                                <flux:button variant="ghost" type="button">Cancel</flux:button>
                            </flux:modal.close>

                            <flux:button type="submit" variant="danger" icon="trash">Delete conversation</flux:button>
                        </div>
                    </form>
                </flux:modal>
                @endif
            </flux:tab.panel>
        @endif

        @if ($this->isMember)
            <flux:tab.panel name="members">
                {{-- Pending Requests (leaders only) --}}
                @if ($this->isLeader && $this->pendingRequests->isNotEmpty())
                    <div class="mb-8">
                        <flux:heading size="lg" class="mb-3">Pending Requests</flux:heading>

                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Name</flux:table.column>
                                <flux:table.column>Requested</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->pendingRequests as $user)
                                    <flux:table.row :key="$user->id">
                                        <flux:table.cell>{{ $user->name }}</flux:table.cell>
                                        <flux:table.cell class="whitespace-nowrap">{{ $user->pivot->created_at->diffForHumans() }}</flux:table.cell>
                                        <flux:table.cell align="end">
                                            <div class="flex gap-2 justify-end">
                                                <flux:button wire:click="approveMember({{ $user->id }})" variant="primary" size="sm" icon="check">Approve</flux:button>
                                                <flux:button wire:click="rejectMember({{ $user->id }})" variant="danger" size="sm" icon="x-mark">Reject</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endif

                {{-- Toolbar --}}
                <div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3 flex-wrap">
                        <flux:input
                            wire:model.live.debounce.300ms="searchMembers"
                            icon="magnifying-glass"
                            placeholder="Search members…"
                            class="!w-full sm:!w-[260px]"
                        />

                        <div role="tablist" class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/60 p-1">
                            @foreach ([
                                'all' => "All · {$this->activeMembers->count()}",
                                'leader' => "Leaders · {$this->leaderCount}",
                                'member' => 'Members · ' . ($this->activeMembers->count() - $this->leaderCount),
                            ] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('memberFilter', '{{ $value }}')"
                                    class="rounded-md px-3 py-1 text-xs font-semibold whitespace-nowrap transition {{ $memberFilter === $value ? 'bg-white shadow-sm text-zinc-800 dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if ($this->isLeader)
                        @include('pages.groups._partials.add-member-popover', [
                            'group' => $group,
                            'availableUsers' => $this->availableUsers,
                            'pickedUsers' => $this->pickedUsers,
                            'memberSearch' => $memberSearch,
                            'addMemberOpen' => $addMemberOpen,
                        ])
                    @endif
                </div>

                {{-- Members card --}}
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <div class="grid items-center gap-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/60 px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                        style="grid-template-columns: 1fr 180px 160px 44px;">
                        <div>Member</div>
                        <div>Role</div>
                        <div>Joined</div>
                        <div></div>
                    </div>

                    @if ($this->filteredMembers->isEmpty())
                        <div class="px-4 py-12 text-center text-[13px] text-zinc-500 dark:text-zinc-400">
                            No members match your search.
                        </div>
                    @else
                        @foreach ($this->filteredMembers as $user)
                            @include('pages.groups._partials.member-row', [
                                'user' => $user,
                                'isLeader' => $this->isLeader,
                                'isOnlyLeader' => $user->pivot->role === GroupRole::LEADER && $this->leaderCount === 1,
                            ])
                        @endforeach
                    @endif
                </div>
            </flux:tab.panel>
        @endif
    </flux:tab.group>
</section>
