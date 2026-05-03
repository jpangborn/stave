<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
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
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    public Group $group;

    #[Url]
    public string $tab = 'conversations';

    public string $memberSearch = '';
    public ?int $selectedUserId = null;

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
        return $this->membership?->status === MembershipStatus::ACTIVE;
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
    public function conversationsVisible(): bool
    {
        return $this->isMember && $this->group->messaging !== GroupMessaging::OFF;
    }

    /** @return Collection<int, Conversation> */
    #[Computed]
    public function conversations(): Collection
    {
        return $this->group->conversations()
            ->with('creator')
            ->withCount('comments')
            ->orderByRaw('COALESCE(last_comment_at, created_at) desc')
            ->get();
    }

    /** @return Collection<int, User>|BaseCollection<int, User> */
    #[Computed]
    public function availableUsers(): Collection|BaseCollection
    {
        if (strlen($this->memberSearch) < 2) {
            return collect();
        }

        $existingUserIds = $this->group->allUsers()
            ->whereIn('group_user.status', [MembershipStatus::ACTIVE, MembershipStatus::PENDING])
            ->pluck('users.id');

        return User::query()
            ->where('name', 'like', "%{$this->memberSearch}%")
            ->whereNotIn('id', $existingUserIds)
            ->limit(20)
            ->get();
    }

    public function join(): void
    {
        $this->authorize('join', $this->group);

        $existing = $this->group->allUsers()->where('user_id', Auth::id())->exists();

        if ($existing) {
            $this->group->allUsers()->updateExistingPivot(Auth::id(), [
                'status' => MembershipStatus::PENDING,
            ]);
        } else {
            $this->group->allUsers()->attach(Auth::id(), [
                'role' => GroupRole::MEMBER,
                'status' => MembershipStatus::PENDING,
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

        if ($membership?->status !== MembershipStatus::PENDING) {
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
                'status' => MembershipStatus::ACTIVE,
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
                'status' => MembershipStatus::REJECTED,
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

    public function addMember(): void
    {
        $this->authorize('manageMembers', $this->group);

        if (! $this->selectedUserId) {
            return;
        }

        $user = User::findOrFail($this->selectedUserId);

        if ($this->group->members()->where('user_id', $user->id)->exists()) {
            Flux::toast(variant: 'warning', text: "{$user->name} is already a member.");
            $this->reset('selectedUserId', 'memberSearch');

            return;
        }

        $existing = $this->group->allUsers()->where('user_id', $user->id)->exists();

        if ($existing) {
            $this->group->allUsers()->updateExistingPivot($user->id, [
                'status' => MembershipStatus::ACTIVE,
                'role' => GroupRole::MEMBER,
            ]);
        } else {
            $this->group->allUsers()->attach($user->id, [
                'role' => GroupRole::MEMBER,
                'status' => MembershipStatus::ACTIVE,
            ]);
        }

        $user->notify(new GroupMemberAddedNotification($this->group, Auth::user()));

        $this->reset('selectedUserId', 'memberSearch');
        unset($this->activeMembers, $this->pendingRequests, $this->availableUsers);
        Flux::toast(text: "{$user->name} added to group.");
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
    <div class="flex items-start justify-between">
        <div class="flex items-start gap-4">
            @if ($group->image)
                <img src="{{ Storage::disk('digital-ocean')->url($group->image) }}" alt="{{ $group->name }}" class="size-24 rounded-lg object-cover" />
            @endif

            <div>
                <flux:heading size="xl" level="1">{{ $group->name }}</flux:heading>
                <flux:subheading size="lg">
                    <flux:badge size="sm" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
                    <flux:badge size="sm" :color="$group->messaging->color()">{{ $group->messaging->label() }}</flux:badge>
                    <span class="ml-2">{{ $this->activeMembers->count() }} {{ str('member')->plural($this->activeMembers->count()) }}</span>
                </flux:subheading>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $group)
                <flux:button :href="route('groups.edit', $group)" variant="primary" icon="pencil-square">Edit</flux:button>
            @endcan

            @if ($this->membership === null && $group->visibility === GroupVisibility::PUBLIC)
                <flux:button wire:click="join" variant="primary" icon="user-plus">Request to Join</flux:button>
            @elseif ($this->membership?->status === MembershipStatus::PENDING)
                <div class="flex items-center gap-2">
                    <flux:badge color="amber">Request Pending</flux:badge>
                    <flux:button wire:click="cancelRequest" variant="ghost" size="sm">Cancel</flux:button>
                </div>
            @elseif ($this->membership?->status === MembershipStatus::ACTIVE && ! $this->isLeader)
                <flux:button wire:click="leave" variant="danger" icon="arrow-right-start-on-rectangle"
                    wire:confirm="Are you sure you want to leave this group?">Leave Group</flux:button>
            @elseif ($this->membership?->status === MembershipStatus::REJECTED)
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
        <flux:tabs wire:model="tab">
            @if ($this->conversationsVisible)
                <flux:tab name="conversations" icon="chat-bubble-left-right">Conversations</flux:tab>
            @endif
            @if ($this->isMember)
                <flux:tab name="members" icon="user-group">
                    Members
                    @if ($this->isLeader && $this->pendingRequests->isNotEmpty())
                        <flux:badge size="sm" color="amber" class="ml-1">{{ $this->pendingRequests->count() }}</flux:badge>
                    @endif
                </flux:tab>
            @endif
        </flux:tabs>

        @if ($this->conversationsVisible)
            <flux:tab.panel name="conversations">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Conversations</flux:heading>
                    @can('create', [Conversation::class, $group])
                        <flux:button :href="route('groups.conversations.create', $group)" variant="primary" size="sm" icon="plus" wire:navigate>
                            New Conversation
                        </flux:button>
                    @endcan
                </div>

                @if ($this->conversations->isEmpty())
                    <div class="text-center py-12">
                        <flux:icon.chat-bubble-left-right class="mx-auto size-12 text-zinc-400" />
                        <flux:heading size="lg" class="mt-4">No conversations yet</flux:heading>
                        @can('create', [Conversation::class, $group])
                            <flux:subheading>Start the first conversation in this group.</flux:subheading>
                        @endcan
                    </div>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Title</flux:table.column>
                            <flux:table.column>Started by</flux:table.column>
                            <flux:table.column>Messages</flux:table.column>
                            <flux:table.column>Last activity</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->conversations as $conversation)
                                <flux:table.row :key="$conversation->id">
                                    <flux:table.cell>
                                        <flux:link :href="route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation])" wire:navigate>
                                            {{ $conversation->title }}
                                        </flux:link>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $conversation->creator?->name ?? 'Unknown' }}</flux:table.cell>
                                    <flux:table.cell>{{ $conversation->comments_count }}</flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">
                                        {{ ($conversation->last_comment_at ?? $conversation->created_at)->diffForHumans() }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
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

                {{-- Active Members --}}
                <div class="mb-8">
                    <flux:heading size="lg" class="mb-3">Members</flux:heading>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Name</flux:table.column>
                            <flux:table.column>Role</flux:table.column>
                            <flux:table.column>Joined</flux:table.column>
                            @if ($this->isLeader)
                                <flux:table.column></flux:table.column>
                            @endif
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->activeMembers as $user)
                                <flux:table.row :key="$user->id">
                                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" inset="top bottom" :color="$user->pivot->role->color()">
                                            {{ $user->pivot->role->label() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">{{ $user->pivot->created_at->diffForHumans() }}</flux:table.cell>
                                    @if ($this->isLeader)
                                        <flux:table.cell align="end">
                                            @if ($user->id !== Auth::id())
                                                <flux:button wire:click="removeMember({{ $user->id }})" variant="ghost" size="sm" icon="trash"
                                                    wire:confirm="Remove {{ $user->name }} from this group?" />
                                            @endif
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Add Member (leaders only) --}}
                @if ($this->isLeader)
                    <div>
                        <flux:heading size="lg" class="mb-3">Add Member</flux:heading>

                        <form wire:submit="addMember" class="flex items-end gap-3 max-w-md">
                            <div class="flex-1">
                                <flux:select wire:model="selectedUserId" variant="combobox" :filter="false" placeholder="Search users..." clearable>
                                    <x-slot name="input">
                                        <flux:select.input wire:model.live.debounce.300ms="memberSearch" placeholder="Search by name..." />
                                    </x-slot>

                                    @foreach ($this->availableUsers as $user)
                                        <flux:select.option :value="$user->id" wire:key="add-{{ $user->id }}">{{ $user->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:button type="submit" variant="primary" icon="user-plus">Add</flux:button>
                        </form>
                    </div>
                @endif
            </flux:tab.panel>
        @endif
    </flux:tab.group>
</section>
