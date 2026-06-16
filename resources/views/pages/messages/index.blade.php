<?php

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Services\ScriptureLinker;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    public ?int $activeConversationId = null;

    /** @var 'thread'|'compose'|'empty' */
    public string $mode = 'empty';

    #[Url(as: 'q')]
    public string $search = '';

    /** @var array<int, int> */
    public array $composeRecipients = [];

    public string $composeQuery = '';

    public ?int $addPersonFrom = null;

    public string $reply = '';

    public function mount(?Conversation $conversation = null): void
    {
        if ($conversation === null) {
            return;
        }

        abort_unless(
            $conversation->group->is_direct && $conversation->group->hasActiveMember($this->user()),
            404,
        );

        $this->activeConversationId = $conversation->id;
        $this->mode = 'thread';
        $this->markRead($conversation);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /* ----------------------------- list ----------------------------- */

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function threads(): Collection
    {
        $user = $this->user();
        $search = Str::lower(trim($this->search));

        return $user
            ->directConversations()
            ->with(['group.members', 'lastComment.commentator', 'participants'])
            ->get()
            ->sortByDesc(fn (Conversation $conversation): int => $conversation->last_comment_at?->getTimestamp() ?? 0)
            ->map(fn (Conversation $conversation): array => $this->rowFor($conversation, $user))
            ->when($search !== '', fn (Collection $rows): Collection => $rows->filter(
                fn (array $row): bool => str_contains(Str::lower($row['title']), $search)
                    || str_contains(Str::lower($row['preview']), $search),
            ))
            ->values();
    }

    /** @return array<string, mixed> */
    private function rowFor(Conversation $conversation, User $user): array
    {
        $others = $conversation->otherMembersFor($user);
        $isGroup = $others->count() > 1;

        return [
            'id' => $conversation->id,
            'title' => $conversation->displayTitleFor($user),
            'preview' => $this->previewFor($conversation, $user, $isGroup),
            'when' => $this->relativeTime($conversation->last_comment_at),
            'unread' => $conversation->unreadCountFor($user),
            'isGroup' => $isGroup,
            'members' => $others,
        ];
    }

    private function previewFor(Conversation $conversation, User $user, bool $isGroup): string
    {
        /** @var ?Comment $last */
        $last = $conversation->lastComment->first();

        if ($last === null) {
            return 'No messages yet';
        }

        $text = Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($last->text))), 60);
        $author = $last->commentator;

        if ($author instanceof User && $author->id === $user->id) {
            return 'You: '.$text;
        }

        if ($author instanceof User && $isGroup) {
            return Str::of($author->name)->trim()->before(' ').': '.$text;
        }

        return $text;
    }

    private function relativeTime(?Carbon $when): string
    {
        if ($when === null) {
            return '';
        }

        $now = now();

        if ($when->isToday()) {
            $minutes = (int) $when->diffInMinutes($now);

            if ($minutes < 1) {
                return 'now';
            }

            if ($minutes < 60) {
                return $minutes.'m';
            }

            return (int) $when->diffInHours($now).'h';
        }

        if ($when->isYesterday()) {
            return 'Yesterday';
        }

        if ($when->greaterThan($now->copy()->subDays(7))) {
            return $when->format('D');
        }

        return $when->format('M j');
    }

    /* ---------------------------- thread ---------------------------- */

    #[Computed]
    public function activeConversation(): ?Conversation
    {
        if ($this->activeConversationId === null) {
            return null;
        }

        return $this->user()
            ->directConversations()
            ->with(['group.members'])
            ->whereKey($this->activeConversationId)
            ->first();
    }

    /** @return EloquentCollection<int, Comment> */
    #[Computed]
    public function messages(): EloquentCollection
    {
        $conversation = $this->activeConversation;

        if ($conversation === null) {
            return new EloquentCollection();
        }

        /** @var EloquentCollection<int, Comment> */
        return $conversation->comments()
            ->with('commentator')
            ->orderBy('created_at')
            ->get();
    }

    /** @return Collection<string, EloquentCollection<int, Comment>> */
    #[Computed]
    public function groupedMessages(): Collection
    {
        return $this->messages->groupBy(fn (Comment $comment): string => $comment->created_at->toDateString());
    }

    public function dayLabel(string $dateString): string
    {
        $date = Carbon::parse($dateString);

        return match (true) {
            $date->isToday() => 'Today',
            $date->isYesterday() => 'Yesterday',
            $date->isCurrentYear() => $date->format('M j'),
            default => $date->format('M j, Y'),
        };
    }

    #[Computed]
    public function isMuted(): bool
    {
        $conversation = $this->activeConversation;

        return $conversation !== null && $this->user()->hasMuted($conversation);
    }

    /* --------------------------- compose ---------------------------- */

    /** @return EloquentCollection<int, User> */
    #[Computed]
    public function suggestions(): EloquentCollection
    {
        $query = trim($this->composeQuery);

        /** @var EloquentCollection<int, User> */
        return User::query()
            ->where('id', '!=', $this->user()->id)
            ->whereNotIn('id', $this->composeRecipients)
            ->when($query !== '', fn ($builder) => $builder->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
            ))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function recipientChips(): Collection
    {
        if ($this->composeRecipients === []) {
            return collect();
        }

        $order = array_flip($this->composeRecipients);

        return User::query()
            ->whereIn('id', $this->composeRecipients)
            ->get()
            ->sortBy(fn (User $user): int => $order[$user->id] ?? 0)
            ->values();
    }

    /** @return array{tone: string, title: string, body: string}|null */
    #[Computed]
    public function noteBanner(): ?array
    {
        $recipients = $this->composeRecipients;
        $count = count($recipients);

        if ($count === 1) {
            $existing = $this->existingOneToOne($recipients[0]);
            $recipient = User::find($recipients[0]);
            $name = $recipient instanceof User ? $recipient->name : 'this person';

            if ($existing && $this->addPersonFrom !== null) {
                return ['tone' => 'info', 'title' => 'Same person', 'body' => "You removed everyone you added — this will continue your existing conversation with {$name}."];
            }

            if ($existing) {
                return ['tone' => 'info', 'title' => 'Existing conversation', 'body' => "You already message {$name}. Your message will be added to that thread instead of starting a new one."];
            }

            return null;
        }

        if ($count >= 2) {
            if ($this->addPersonFrom !== null) {
                $from = $this->user()
                    ->directConversations()
                    ->whereKey($this->addPersonFrom)
                    ->first();
                $fromTitle = $from?->displayTitleFor($this->user()) ?? 'this thread';

                return ['tone' => 'warn', 'title' => 'This starts a new conversation', 'body' => "Stave keeps one conversation per set of people. Your existing thread ({$fromTitle}) stays exactly as it is — these messages won’t appear there."];
            }

            return ['tone' => 'neutral', 'title' => 'New group conversation', 'body' => "This creates a group conversation with {$count} people. Everyone can see messages from here on."];
        }

        return null;
    }

    private function existingOneToOne(int $otherUserId): ?Group
    {
        $key = Group::directKeyFor([$this->user()->id, $otherUserId]);

        return Group::query()->direct()->where('direct_key', $key)->first();
    }

    /* --------------------------- actions ---------------------------- */

    public function openConversation(int $conversationId): void
    {
        $conversation = $this->user()->directConversations()->whereKey($conversationId)->first();

        if ($conversation === null) {
            return;
        }

        $this->activeConversationId = $conversation->id;
        $this->mode = 'thread';
        $this->reset('composeRecipients', 'composeQuery', 'addPersonFrom', 'reply');
        $this->markRead($conversation);

        unset($this->activeConversation, $this->messages, $this->groupedMessages, $this->threads, $this->isMuted);
        $this->dispatch('messages-unread-updated');
    }

    public function newMessage(): void
    {
        $this->mode = 'compose';
        $this->activeConversationId = null;
        $this->reset('composeRecipients', 'composeQuery', 'addPersonFrom', 'reply');

        unset($this->activeConversation, $this->messages, $this->suggestions, $this->recipientChips, $this->noteBanner);
    }

    public function beginAddPeople(): void
    {
        $conversation = $this->activeConversation;

        if ($conversation === null) {
            return;
        }

        $this->addPersonFrom = $conversation->id;
        $this->composeRecipients = $conversation->otherMembersFor($this->user())->pluck('id')->all();
        $this->composeQuery = '';
        $this->reply = '';
        $this->mode = 'compose';
        $this->activeConversationId = null;

        unset($this->activeConversation, $this->messages, $this->suggestions, $this->recipientChips, $this->noteBanner);
    }

    public function addRecipient(int $userId): void
    {
        if (! in_array($userId, $this->composeRecipients, true)) {
            $this->composeRecipients[] = $userId;
        }

        $this->composeQuery = '';
        unset($this->suggestions, $this->recipientChips, $this->noteBanner);
    }

    public function removeRecipient(int $userId): void
    {
        $this->composeRecipients = array_values(array_filter(
            $this->composeRecipients,
            fn (int $id): bool => $id !== $userId,
        ));

        unset($this->suggestions, $this->recipientChips, $this->noteBanner);
    }

    public function sendThread(): void
    {
        $conversation = $this->activeConversation;

        if ($conversation === null) {
            return;
        }

        $this->authorize('comment', $conversation);

        if (trim(strip_tags($this->reply)) === '') {
            $this->addError('reply', 'Write a message.');

            return;
        }

        $conversation->postComment(trim($this->reply), $this->user());
        $conversation->markReadFor($this->user());

        $this->reply = '';
        unset($this->messages, $this->groupedMessages, $this->threads, $this->activeConversation);
        $this->dispatch('messages-unread-updated');
    }

    public function sendCompose(): void
    {
        if (trim(strip_tags($this->reply)) === '') {
            $this->addError('reply', 'Write a message.');

            return;
        }

        if ($this->composeRecipients === []) {
            $this->addError('composeRecipients', 'Add at least one person.');

            return;
        }

        $user = $this->user();
        $group = Group::findOrCreateDirect($user, $this->composeRecipients);

        /** @var Conversation $conversation */
        $conversation = $group->directConversation()->firstOrFail();

        $this->authorize('comment', $conversation);

        $conversation->postComment(trim($this->reply), $user);
        $conversation->markReadFor($user);

        $this->reset('composeRecipients', 'composeQuery', 'addPersonFrom', 'reply');
        $this->openConversation($conversation->id);
    }

    public function cancelCompose(): void
    {
        $return = $this->addPersonFrom;
        $this->reset('composeRecipients', 'composeQuery', 'addPersonFrom', 'reply');

        if ($return !== null && $this->user()->directConversations()->whereKey($return)->exists()) {
            $this->openConversation($return);

            return;
        }

        $first = $this->user()->directConversations()->orderByDesc('last_comment_at')->first();

        if ($first !== null) {
            $this->openConversation($first->id);

            return;
        }

        $this->mode = 'empty';
        $this->activeConversationId = null;
    }

    public function toggleMute(): void
    {
        $conversation = $this->activeConversation;

        if ($conversation === null) {
            return;
        }

        $user = $this->user();

        if ($user->hasMuted($conversation)) {
            $user->mutedCommentables()
                ->where('commentable_type', $conversation->getMorphClass())
                ->where('commentable_id', $conversation->getKey())
                ->delete();
        } else {
            $user->mutedCommentables()->create([
                'commentable_type' => $conversation->getMorphClass(),
                'commentable_id' => $conversation->getKey(),
            ]);
        }

        unset($this->isMuted);
    }

    public function deleteConversation(): void
    {
        $conversation = $this->activeConversation;

        if ($conversation === null) {
            return;
        }

        abort_unless($conversation->group->hasActiveMember($this->user()), 403);

        $this->activeConversationId = null;
        $this->mode = 'empty';
        $this->reply = '';

        $conversation->comments()->delete();
        $conversation->notificationSubscriptions()->delete();
        $conversation->group->delete();

        unset($this->threads, $this->activeConversation, $this->messages, $this->groupedMessages);
        $this->dispatch('messages-unread-updated');
    }

    public function handleIncoming(?int $conversationId = null): void
    {
        unset($this->threads);

        if ($conversationId !== null && $conversationId === $this->activeConversationId) {
            $conversation = $this->activeConversation;

            if ($conversation !== null) {
                $this->markRead($conversation);
            }

            unset($this->messages, $this->groupedMessages, $this->activeConversation);
        }

        $this->dispatch('messages-unread-updated');
    }

    private function markRead(Conversation $conversation): void
    {
        $user = $this->user();
        $conversation->markReadFor($user);

        $user->unreadNotifications()
            ->where('data->conversation_id', $conversation->id)
            ->update(['read_at' => now()]);
    }
}; ?>

<section class="-m-6 lg:-m-8 flex h-[calc(100vh-3rem)] lg:h-screen min-h-0" data-test="messages-page">
    {{-- ============ LEFT RAIL ============ --}}
    <div class="flex w-[344px] shrink-0 flex-col border-e border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-4 pb-3 pt-4 dark:border-zinc-700">
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="xl" class="font-extrabold tracking-tight">{{ __('Messages') }}</flux:heading>
                <flux:button size="sm" variant="primary" icon="pencil-square" wire:click="newMessage" data-test="new-message">
                    {{ __('New') }}
                </flux:button>
            </div>

            <flux:input
                size="sm"
                icon="magnifying-glass"
                placeholder="{{ __('Search messages…') }}"
                wire:model.live.debounce.250ms="search"
                data-test="messages-search"
            />
        </div>

        <div class="flex-1 overflow-y-auto p-2" data-test="conversation-list">
            <div class="flex items-center gap-2 px-3 pb-1.5 pt-2.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Direct Messages') }}</span>
                <flux:badge size="sm" color="zinc">{{ $this->threads->count() }}</flux:badge>
            </div>

            @forelse ($this->threads as $row)
                <button
                    type="button"
                    wire:key="thread-{{ $row['id'] }}"
                    wire:click="openConversation({{ $row['id'] }})"
                    @class([
                        'group flex w-full items-center gap-3 rounded-lg px-3.5 py-2.5 text-left transition',
                        'bg-accent/10 shadow-[inset_3px_0_0_var(--color-accent)]' => $row['id'] === $activeConversationId && $mode === 'thread',
                        'hover:bg-zinc-100 dark:hover:bg-zinc-800' => ! ($row['id'] === $activeConversationId && $mode === 'thread'),
                    ])
                    data-test="conversation-row"
                >
                    <div class="shrink-0">
                        @if ($row['isGroup'])
                            <flux:avatar.group>
                                @foreach ($row['members']->take(2) as $member)
                                    <flux:avatar size="sm" :name="$member->name" :src="$member->gravatar" color="auto" />
                                @endforeach
                            </flux:avatar.group>
                        @else
                            <flux:avatar size="lg" :name="$row['members']->first()?->name" :src="$row['members']->first()?->gravatar" color="auto" />
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <span @class([
                                'flex-1 truncate text-sm tracking-tight text-zinc-900 dark:text-zinc-100',
                                'font-extrabold' => $row['unread'] > 0,
                                'font-semibold' => $row['unread'] === 0,
                            ])>{{ $row['title'] }}</span>
                            <span @class([
                                'shrink-0 text-[11px]',
                                'font-bold text-accent' => $row['unread'] > 0,
                                'font-medium text-zinc-400' => $row['unread'] === 0,
                            ])>{{ $row['when'] }}</span>
                        </div>
                        <div class="mt-0.5 flex items-center gap-1.5">
                            <span @class([
                                'min-w-0 flex-1 truncate text-[13px]',
                                'font-semibold text-zinc-600 dark:text-zinc-300' => $row['unread'] > 0,
                                'text-zinc-500 dark:text-zinc-400' => $row['unread'] === 0,
                            ])>{{ $row['preview'] }}</span>
                            @if ($row['unread'] > 0)
                                <span class="grid h-[18px] min-w-[18px] place-items-center rounded-full bg-accent px-1.5 text-[11px] font-bold text-white" data-test="unread-badge">
                                    {{ $row['unread'] > 9 ? '9+' : $row['unread'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="px-4 py-10 text-center text-[13px] text-zinc-500 dark:text-zinc-400">
                    {{ $search !== '' ? __('No conversations match your search.') : __('No messages yet. Start a new conversation.') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- ============ RIGHT PANE ============ --}}
    <div class="flex min-w-0 flex-1 flex-col bg-white dark:bg-zinc-800">
        @if ($mode === 'thread' && $this->activeConversation)
            @php($conversation = $this->activeConversation)
            @php($others = $conversation->otherMembersFor($this->user()))

            {{-- thread header --}}
            <header class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                <div class="shrink-0">
                    @if ($others->count() > 1)
                        <flux:avatar.group>
                            @foreach ($others->take(3) as $member)
                                <flux:avatar size="sm" :name="$member->name" :src="$member->gravatar" color="auto" />
                            @endforeach
                        </flux:avatar.group>
                    @else
                        <flux:avatar size="md" :name="$others->first()?->name" :src="$others->first()?->gravatar" color="auto" />
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <flux:heading class="truncate" size="lg">{{ $conversation->displayTitleFor($this->user()) }}</flux:heading>
                    <div class="truncate text-[12.5px] text-zinc-500 dark:text-zinc-400">{{ $conversation->sublineFor($this->user()) }}</div>
                </div>

                <flux:button size="sm" variant="ghost" icon="user-plus" wire:click="beginAddPeople" data-test="add-people">
                    {{ __('Add people') }}
                </flux:button>

                <flux:dropdown position="bottom" align="end">
                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />

                    <flux:menu>
                        <flux:menu.item icon="{{ $this->isMuted ? 'bell' : 'bell-slash' }}" wire:click="toggleMute">
                            {{ $this->isMuted ? __('Unmute') : __('Mute') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item
                            icon="trash"
                            variant="danger"
                            wire:click="deleteConversation"
                            wire:confirm="{{ __('Delete this conversation for everyone? This cannot be undone.') }}"
                            data-test="delete-conversation"
                        >
                            {{ __('Delete conversation') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </header>

            {{-- messages --}}
            <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5" data-test="messages-area">
                @foreach ($this->groupedMessages as $dateString => $dayMessages)
                    <x-conversation.day-divider :label="$this->dayLabel($dateString)" />

                    @foreach ($dayMessages as $comment)
                        @php($author = $comment->commentator)
                        @php($isMine = $author instanceof App\Models\User && $author->id === $this->user()->id)
                        @php($showAuthor = ! $isMine && $others->count() > 1)

                        <div @class(['flex flex-col gap-1', 'items-end' => $isMine, 'items-start' => ! $isMine]) wire:key="msg-{{ $comment->id }}" data-test="message">
                            <div @class(['flex items-center gap-2', 'flex-row-reverse' => $isMine])>
                                @if ($showAuthor)
                                    <flux:avatar size="xs" :name="$author?->name" :src="$author?->gravatar" color="auto" />
                                    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-200">{{ $author?->name }}</span>
                                @endif
                                <span class="text-[11px] text-zinc-400">{{ $comment->created_at->format('g:i A') }}</span>
                            </div>

                            <div @class([
                                'max-w-[min(560px,78%)] break-words rounded-xl px-3.5 py-2.5 text-sm leading-relaxed',
                                'rounded-tr-sm bg-accent text-white' => $isMine,
                                'rounded-tl-sm bg-zinc-100 text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100' => ! $isMine,
                            ])>
                                <div class="**:[a]:underline **:[strong]:font-semibold **:[em]:italic **:[u]:underline **:[s]:line-through **:[ol]:ml-5 **:[ol]:list-decimal **:[ul]:ml-5 **:[ul]:list-disc **:[blockquote]:border-l-2 **:[blockquote]:pl-2 **:[p]:not-first:mt-2">
                                    {!! app(ScriptureLinker::class)->linkify($comment->text) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- composer --}}
            <div class="px-5 pb-4 pt-3">
                <x-conversation-composer
                    editor-model="reply"
                    submit-action="sendThread"
                    submit-label="Send"
                    :allow-prayer="false"
                    :allow-mentions="false"
                    test-prefix="dm-composer"
                />
            </div>
        @elseif ($mode === 'compose')
            {{-- compose header --}}
            <header class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                <div class="min-w-0 flex-1">
                    <flux:heading size="lg">{{ $addPersonFrom ? __('Add people') : __('New message') }}</flux:heading>
                    <div class="text-[12.5px] text-zinc-500 dark:text-zinc-400">
                        {{ $addPersonFrom ? __('Starts a separate conversation with everyone below') : __('Pick one person for a direct message, or several for a group') }}
                    </div>
                </div>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="cancelCompose" data-test="cancel-compose" />
            </header>

            {{-- To: field --}}
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <div class="flex items-start gap-2" x-data @click="$refs.recipientInput?.focus()">
                    <span class="pt-1.5 text-sm font-semibold text-zinc-600 dark:text-zinc-300">{{ __('To:') }}</span>
                    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                        @foreach ($this->recipientChips as $chip)
                            <span
                                wire:key="chip-{{ $chip->id }}"
                                class="inline-flex h-7 items-center gap-1.5 rounded-full border border-accent/30 bg-accent/10 pl-1 pr-1.5 text-[13px] font-semibold text-accent-content"
                                data-test="recipient-chip"
                            >
                                <flux:avatar size="xs" :name="$chip->name" :src="$chip->gravatar" color="auto" />
                                <span class="text-accent">{{ $chip->name }}</span>
                                <button type="button" wire:click="removeRecipient({{ $chip->id }})" class="grid size-4 place-items-center rounded-full text-accent hover:bg-accent/20" aria-label="Remove">
                                    <flux:icon.x-mark variant="micro" class="size-2.5" />
                                </button>
                            </span>
                        @endforeach

                        <input
                            x-ref="recipientInput"
                            type="text"
                            wire:model.live.debounce.200ms="composeQuery"
                            @if ($this->recipientChips->isNotEmpty())
                                @keydown.backspace="if ($event.target.value === '') $wire.removeRecipient({{ $this->recipientChips->last()->id }})"
                            @endif
                            placeholder="{{ $this->recipientChips->isEmpty() ? __('Type a name…') : '' }}"
                            class="h-7 min-w-[120px] flex-1 border-0 bg-transparent text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100"
                        />
                    </div>
                </div>
                <flux:error name="composeRecipients" class="mt-1.5" />
            </div>

            {{-- body --}}
            <div class="flex-1 overflow-y-auto px-5 py-4">
                @if ($this->noteBanner)
                    @php($tone = $this->noteBanner['tone'])
                    <div @class([
                        'mb-3.5 flex gap-2.5 rounded-xl border px-3.5 py-3',
                        'border-accent/30 bg-accent/10 text-accent' => $tone === 'info',
                        'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200' => $tone === 'warn',
                        'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300' => $tone === 'neutral',
                    ]) data-test="note-banner" data-tone="{{ $tone }}">
                        <flux:icon.information-circle class="mt-0.5 size-4 shrink-0" />
                        <div>
                            <div class="text-[13px] font-bold">{{ $this->noteBanner['title'] }}</div>
                            <div class="mt-0.5 text-[12.5px] leading-relaxed opacity-90">{{ $this->noteBanner['body'] }}</div>
                        </div>
                    </div>
                @endif

                <div class="mb-2 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Suggested') }}</div>

                @forelse ($this->suggestions as $person)
                    <button
                        type="button"
                        wire:key="suggestion-{{ $person->id }}"
                        wire:click="addRecipient({{ $person->id }})"
                        class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        data-test="suggestion"
                    >
                        <flux:avatar size="sm" :name="$person->name" :src="$person->gravatar" color="auto" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $person->name }}</span>
                            <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $person->email }}</span>
                        </span>
                        <flux:icon.plus class="size-4 text-zinc-400" />
                    </button>
                @empty
                    <div class="px-2.5 py-6 text-[13px] text-zinc-500 dark:text-zinc-400">
                        {{ $composeQuery !== '' ? __('No people match.') : __('Everyone is already added.') }}
                    </div>
                @endforelse
            </div>

            {{-- first-message composer --}}
            <div class="border-t border-zinc-200 px-5 pb-4 pt-3 dark:border-zinc-700">
                <x-conversation-composer
                    editor-model="reply"
                    submit-action="sendCompose"
                    :submit-label="count($composeRecipients) >= 2 ? __('Start conversation') : __('Send')"
                    :allow-prayer="false"
                    :allow-mentions="false"
                    test-prefix="dm-compose"
                />
            </div>
        @else
            {{-- empty --}}
            <div class="flex flex-1 flex-col items-center justify-center gap-3.5 p-10 text-center" data-test="messages-empty">
                <div class="grid size-14 place-items-center rounded-2xl bg-accent/10">
                    <flux:icon.chat-bubble-left-right class="size-7 text-accent" />
                </div>
                <flux:heading size="lg">{{ __('Select a conversation') }}</flux:heading>
                <flux:text class="max-w-[300px]">{{ __('Pick a thread on the left, or start a new message to anyone in your congregation.') }}</flux:text>
                <flux:button variant="primary" icon="pencil-square" wire:click="newMessage" class="mt-1">{{ __('New message') }}</flux:button>
            </div>
        @endif
    </div>

    @script
    <script>
        window.addEventListener('stave:notification', (event) => {
            const detail = event.detail ?? {};

            if (detail.is_direct || detail.conversation_id) {
                $wire.handleIncoming(detail.conversation_id ?? null);
            }
        });
    </script>
    @endscript
</section>
