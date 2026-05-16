<?php

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Services\ScriptureLinker;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Comments\Support\Config;

new class extends Component {
    public Group $group;
    public Conversation $conversation;

    public string $reply = '';

    public bool $replyIsPrayer = false;

    public bool $pinnedStripOpen = true;

    public function mount(Group $group, Conversation $conversation): void
    {
        abort_unless($conversation->group_id === $group->id, 404);

        $this->authorize('view', $conversation);

        $this->group = $group;
        $this->conversation = $conversation;
    }

    /** @return Collection<int, Comment> */
    #[Computed]
    public function comments(): Collection
    {
        /** @var Collection<int, Comment> */
        return $this->conversation->comments()
            ->with(['commentator', 'reactions'])
            ->orderBy('created_at')
            ->get();
    }

    /** @return Collection<int, Comment> */
    #[Computed]
    public function pinnedComments(): Collection
    {
        /** @var Collection<int, Comment> */
        return $this->conversation->pinnedComments()
            ->with('pinnedBy')
            ->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function members(): Collection
    {
        /** @var Collection<int, User> */
        return $this->group->members()->orderBy('name')->get();
    }

    public function memberCount(): int
    {
        return $this->members->count();
    }

    /** @return BaseCollection<int, User> */
    #[Computed]
    public function displayedMembers(): BaseCollection
    {
        return $this->members->take(4);
    }

    /** @return BaseCollection<int, User> */
    #[Computed]
    public function contributors(): BaseCollection
    {
        $contributorIds = $this->conversation->comments()
            ->where('commentator_type', (new User)->getMorphClass())
            ->distinct()
            ->pluck('commentator_id');

        return $this->members->filter(fn (User $user): bool => $contributorIds->contains($user->id))->values();
    }

    /** @return BaseCollection<int, User> */
    #[Computed]
    public function nonContributors(): BaseCollection
    {
        $contributorIds = $this->contributors->pluck('id');

        return $this->members->reject(fn (User $user): bool => $contributorIds->contains($user->id))->values();
    }

    /** @return BaseCollection<int|string, Collection<int, Comment>> */
    #[Computed]
    public function groupedComments(): BaseCollection
    {
        return $this->comments->groupBy(fn (Comment $comment): string => $comment->created_at->toDateString())->collect();
    }

    /** @return array<int, string> */
    #[Computed]
    public function memberRoles(): array
    {
        return $this->members->mapWithKeys(function (User $user): array {
            /** @var \App\Models\GroupUser $pivot */
            $pivot = $user->getRelation('pivot');

            return [$user->id => $pivot->role->label()];
        })->all();
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

    public function postReply(): void
    {
        $this->authorize('comment', $this->conversation);

        $validated = $this->validate([
            'reply' => ['required', 'string'],
        ]);

        if (trim(strip_tags($validated['reply'])) === '') {
            $this->addError('reply', 'Please write a reply.');

            return;
        }

        $this->conversation->postComment($validated['reply'], Auth::user(), $this->replyIsPrayer);

        $this->reset('reply', 'replyIsPrayer');
        unset($this->comments, $this->contributors, $this->nonContributors);
    }

    public function react(int $commentId, string $reaction): void
    {
        $this->authorize('comment', $this->conversation);

        abort_unless(in_array($reaction, Config::allowedReactions(), true), 422);

        /** @var Comment $comment */
        $comment = $this->conversation->comments()->findOrFail($commentId);
        $user = Auth::user();

        if ($comment->findReaction($reaction, $user)) {
            $comment->deleteReaction($reaction, $user);
        } else {
            $comment->react($reaction, $user);
        }

        unset($this->comments);
    }

    public function pinComment(int $commentId): void
    {
        /** @var Comment $comment */
        $comment = $this->conversation->comments()->findOrFail($commentId);

        $this->authorize('pin', $comment);

        $comment->pin(Auth::user());

        $this->pinnedStripOpen = true;
        unset($this->comments, $this->pinnedComments);
    }

    public function unpinComment(int $commentId): void
    {
        /** @var Comment $comment */
        $comment = $this->conversation->comments()->findOrFail($commentId);

        $this->authorize('unpin', $comment);

        $comment->unpin();

        unset($this->comments, $this->pinnedComments);
    }

    public function togglePrayer(int $commentId): void
    {
        /** @var Comment $comment */
        $comment = $this->conversation->comments()->findOrFail($commentId);

        $this->authorize('markPrayer', $comment);

        $comment->togglePrayer();

        unset($this->comments);
    }

    public function dismissPinnedStrip(): void
    {
        $this->pinnedStripOpen = false;
    }

    public function deleteConversation(): void
    {
        $this->authorize('delete', $this->conversation);

        $this->conversation->delete();

        Flux::toast(variant: 'success', text: 'Conversation deleted.');

        $this->redirect(route('groups.show', $this->group), navigate: true);
    }
};
?>

<section class="-m-6 lg:-m-8 flex h-[calc(100vh-3rem)] lg:h-screen min-h-0">
    {{-- Conversation column --}}
    <div class="flex min-w-0 flex-1 flex-col bg-white dark:bg-zinc-800">
        {{-- Header --}}
        <header class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a
                            href="{{ route('groups.show', $group) }}"
                            wire:navigate
                            title="Back to {{ $group->name }}"
                            aria-label="Back to {{ $group->name }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 py-1 pl-2 pr-2.5 text-xs font-semibold leading-none text-zinc-900 transition-colors duration-100 hover:bg-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600"
                            data-test="back-to-group"
                        >
                            <flux:icon.arrow-left variant="micro" class="size-3" />
                            {{ $group->name }}
                        </a>
                        <flux:heading size="xl" level="1">{{ $conversation->title }}</flux:heading>
                    </div>
                    <flux:subheading class="mt-1">
                        Started by {{ $conversation->creator?->name ?? 'Unknown' }}
                        · {{ $conversation->created_at->diffForHumans() }}
                        @php($memberCount = $this->memberCount())
                        · {{ $memberCount }} {{ str('member')->plural($memberCount) }}
                        @php($commentCount = $this->comments->count())
                        · {{ $commentCount }} {{ str('comment')->plural($commentCount) }}
                    </flux:subheading>
                </div>

                <div class="flex items-center gap-2">
                    <flux:avatar.group class="dark:**:ring-zinc-800">
                        @foreach ($this->displayedMembers as $member)
                            <flux:avatar
                                wire:key="header-avatar-{{ $member->id }}"
                                size="xs"
                                name="{{ $member->name }}"
                                src="{{ $member->gravatar }}"
                                color="auto"
                            />
                        @endforeach
                        @if ($this->memberCount() > 4)
                            <flux:avatar size="xs">+{{ $this->memberCount() - 4 }}</flux:avatar>
                        @endif
                    </flux:avatar.group>

                    <flux:tooltip content="Search (coming soon)">
                        <flux:button variant="ghost" size="sm" icon="magnifying-glass" square disabled />
                    </flux:tooltip>

                    @can('delete', $conversation)
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" square />
                            <flux:menu>
                                <flux:menu.item
                                    wire:click="deleteConversation"
                                    icon="trash"
                                    variant="danger"
                                    wire:confirm="Delete this conversation and all its messages?"
                                >Delete conversation</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    @endcan
                </div>
            </div>

        </header>

        {{-- Pinned strip --}}
        @if ($pinnedStripOpen && $this->pinnedComments->isNotEmpty())
            @php($firstPinned = $this->pinnedComments->first())
            <div class="mx-6 mt-4 flex items-start gap-2.5 rounded-lg border border-accent/30 bg-accent/5 px-3 py-2.5" data-test="pinned-strip">
                <div class="flex size-6 shrink-0 items-center justify-center rounded-md border border-accent/30 bg-white text-accent dark:bg-zinc-900">
                    <flux:icon.bookmark variant="micro" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold uppercase tracking-wide text-accent">
                        Pinned by {{ $firstPinned->pinnedBy?->name ?? 'Unknown' }}
                    </div>
                    <div class="mt-0.5 truncate text-sm text-zinc-700 dark:text-zinc-200">
                        {{ Str::limit(strip_tags($firstPinned->text), 140) }}
                    </div>
                </div>
                <flux:button wire:click="dismissPinnedStrip" variant="ghost" size="sm" icon="x-mark" square aria-label="Dismiss pinned message" />
            </div>
        @endif

        {{-- Messages --}}
        <div class="flex-1 overflow-auto px-6 py-4">
            <div>
                @php($currentUser = Auth::user())
                @php($canComment = $currentUser?->can('comment', $conversation) ?? false)
                @php($quickReactions = array_slice(Config::allowedReactions(), 0, 6))

                @if ($this->comments->isEmpty())
                    <div class="flex flex-col items-center justify-center px-6 py-16 text-center" data-test="empty-state">
                        <flux:icon.chat-bubble-left-right class="size-10 text-zinc-300 dark:text-zinc-600" />
                        <flux:heading size="lg" class="mt-4">No messages yet</flux:heading>
                        <flux:subheading class="mt-1">
                            @if ($canComment)
                                Kick things off — your reply will start the thread.
                            @else
                                Be the first to post here once messaging opens up.
                            @endif
                        </flux:subheading>
                    </div>
                @endif

                @foreach ($this->groupedComments as $dateString => $dayComments)
                    <div class="my-2 flex items-center gap-3.5 px-2 py-3" data-test="day-divider">
                        <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                            {{ $this->dayLabel($dateString) }}
                        </span>
                        <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                    </div>

                    @foreach ($dayComments as $comment)
                        @php($isMine = $comment->commentator?->is($currentUser))
                        @php($role = $this->memberRoles[$comment->commentator_id] ?? null)

                        <div
                            wire:key="comment-{{ $comment->id }}"
                            @class([
                                'group/row relative flex gap-3 rounded-md border-l-2 px-3 py-3 transition-colors',
                                'border-transparent hover:bg-zinc-50 dark:hover:bg-zinc-900/60' => ! $isMine,
                                'border-accent bg-accent/5 hover:bg-accent/10' => $isMine,
                            ])
                            data-test="message-row"
                            @if ($isMine) data-test-mine="true" @endif
                        >
                            <flux:avatar size="md" name="{{ $comment->commentator?->name }}" src="{{ $comment->commentator?->gravatar }}" color="auto" />

                            <div class="min-w-0 flex-1">
                                {{-- Header line --}}
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                    <span @class([
                                        'text-sm font-bold',
                                        'text-accent' => $isMine,
                                        'text-zinc-900 dark:text-white' => ! $isMine,
                                    ])>
                                        {{ $comment->commentator?->name ?? 'Unknown' }}@if ($isMine) <span class="font-normal text-zinc-500">(you)</span>@endif
                                    </span>
                                    @if ($role)
                                        <span class="text-xs text-zinc-500">{{ $role }}</span>
                                    @endif
                                    <span class="text-xs text-zinc-400">· {{ $comment->created_at->diffForHumans() }}</span>

                                    @if ($comment->isPinned())
                                        <flux:badge size="sm" color="green" inset="top bottom" data-test="pinned-badge">Pinned</flux:badge>
                                    @endif
                                    @if ($comment->is_prayer)
                                        <flux:badge size="sm" color="amber" inset="top bottom" data-test="prayer-badge">Prayer</flux:badge>
                                    @endif
                                </div>

                                {{-- Body --}}
                                <div class="mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-200
                                    **:[h1]:text-xl **:[h1]:font-semibold **:[h2]:text-lg **:[h2]:font-semibold **:[h3]:font-semibold
                                    **:[strong]:font-semibold **:[em]:italic **:[u]:underline **:[s]:line-through
                                    **:[ol]:list-decimal **:[ol]:ml-5 **:[ul]:list-disc **:[ul]:ml-5
                                    **:[a]:underline
                                    **:[blockquote]:border-l-4 **:[blockquote]:pl-2">
                                    {!! app(ScriptureLinker::class)->linkify($comment->text) !!}
                                </div>

                                {{-- Reactions row --}}
                                @php($summary = $comment->reactions->summary($currentUser))
                                @if ($summary->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        @foreach ($summary as $reaction)
                                            @php($mine = (bool) ($reaction['commentator_reacted'] ?? false))
                                            @if ($canComment)
                                                <button
                                                    wire:key="reaction-{{ $comment->id }}-{{ $reaction['reaction'] }}"
                                                    wire:click="react({{ $comment->id }}, '{{ $reaction['reaction'] }}')"
                                                    type="button"
                                                    @class([
                                                        'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors',
                                                        'border-accent bg-accent/10 text-accent' => $mine,
                                                        'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => ! $mine,
                                                    ])
                                                    data-test="reaction-chip"
                                                    @if ($mine) data-test-mine="true" @endif
                                                >
                                                    <span>{{ $reaction['reaction'] }}</span>
                                                    <span>{{ $reaction['count'] }}</span>
                                                </button>
                                            @else
                                                <span
                                                    wire:key="reaction-{{ $comment->id }}-{{ $reaction['reaction'] }}"
                                                    class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-2 py-0.5 text-xs font-semibold text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                                >
                                                    <span>{{ $reaction['reaction'] }}</span>
                                                    <span>{{ $reaction['count'] }}</span>
                                                </span>
                                            @endif
                                        @endforeach

                                        @if ($canComment)
                                            <flux:dropdown align="start">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-6 items-center justify-center rounded-full border border-dashed border-zinc-300 px-2 text-xs text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-600 dark:hover:bg-zinc-800"
                                                    aria-label="Add reaction"
                                                    data-test="reaction-picker-trigger"
                                                >
                                                    <flux:icon.face-smile variant="micro" />
                                                </button>
                                                <flux:popover>
                                                    <div class="flex">
                                                        @foreach ($quickReactions as $allowedReaction)
                                                            <flux:button size="sm" variant="ghost" square wire:click="react({{ $comment->id }}, '{{ $allowedReaction }}')">{{ $allowedReaction }}</flux:button>
                                                        @endforeach
                                                    </div>
                                                </flux:popover>
                                            </flux:dropdown>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Hover toolbar --}}
                            @if ($canComment)
                                <div
                                    class="absolute right-3 top-2.5 z-10 hidden gap-0.5 group-hover/row:flex group-focus-within/row:flex group-has-[[data-flux-popover][data-open]]/row:flex"
                                    data-test="hover-toolbar"
                                    role="toolbar"
                                    aria-label="Message actions"
                                >
                                    <flux:dropdown align="end">
                                        <flux:tooltip content="Add reaction">
                                            <button
                                                type="button"
                                                aria-label="Add reaction"
                                                @class([
                                                    'flex size-[26px] cursor-pointer items-center justify-center rounded-md transition-colors duration-[120ms] focus-visible:outline-none',
                                                    'text-accent hover:bg-accent/20 focus-visible:bg-accent/20' => $isMine,
                                                    'text-zinc-500 hover:bg-zinc-200 hover:text-zinc-800 focus-visible:bg-zinc-200 focus-visible:text-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus-visible:bg-zinc-700 dark:focus-visible:text-white' => ! $isMine,
                                                ])
                                            >
                                                <flux:icon.face-smile variant="micro" class="size-3.5" />
                                            </button>
                                        </flux:tooltip>
                                        <flux:popover>
                                            <div class="flex">
                                                @foreach ($quickReactions as $allowedReaction)
                                                    <flux:button size="sm" variant="ghost" square wire:click="react({{ $comment->id }}, '{{ $allowedReaction }}')">{{ $allowedReaction }}</flux:button>
                                                @endforeach
                                            </div>
                                        </flux:popover>
                                    </flux:dropdown>

                                    @can('markPrayer', $comment)
                                        @php($prayerLabel = $comment->is_prayer ? 'Unmark prayer' : 'Mark as prayer')
                                        <flux:tooltip content="{{ $prayerLabel }}">
                                            <button
                                                type="button"
                                                wire:click="togglePrayer({{ $comment->id }})"
                                                aria-label="{{ $prayerLabel }}"
                                                aria-pressed="{{ $comment->is_prayer ? 'true' : 'false' }}"
                                                @class([
                                                    'flex size-[26px] cursor-pointer items-center justify-center rounded-md transition-colors duration-[120ms] focus-visible:outline-none',
                                                    'bg-accent/20 text-accent' => $isMine && $comment->is_prayer,
                                                    'text-accent hover:bg-accent/20 focus-visible:bg-accent/20' => $isMine && ! $comment->is_prayer,
                                                    'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-white' => ! $isMine && $comment->is_prayer,
                                                    'text-zinc-500 hover:bg-zinc-200 hover:text-zinc-800 focus-visible:bg-zinc-200 focus-visible:text-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus-visible:bg-zinc-700 dark:focus-visible:text-white' => ! $isMine && ! $comment->is_prayer,
                                                ])
                                                data-test="prayer-toggle"
                                                @if ($comment->is_prayer) data-test-active="true" @endif
                                            >
                                                <flux:icon.hand-raised variant="micro" class="size-3.5" />
                                            </button>
                                        </flux:tooltip>
                                    @endcan

                                    @can('pin', $comment)
                                        @php($pinned = $comment->isPinned())
                                        @php($pinLabel = $pinned ? 'Unpin from top' : 'Pin to top')
                                        <flux:tooltip content="{{ $pinLabel }}">
                                            <button
                                                type="button"
                                                wire:click="{{ $pinned ? 'unpinComment' : 'pinComment' }}({{ $comment->id }})"
                                                aria-label="{{ $pinLabel }}"
                                                aria-pressed="{{ $pinned ? 'true' : 'false' }}"
                                                @class([
                                                    'flex size-[26px] cursor-pointer items-center justify-center rounded-md transition-colors duration-[120ms] focus-visible:outline-none',
                                                    'bg-accent/20 text-accent' => $isMine && $pinned,
                                                    'text-accent hover:bg-accent/20 focus-visible:bg-accent/20' => $isMine && ! $pinned,
                                                    'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-white' => ! $isMine && $pinned,
                                                    'text-zinc-500 hover:bg-zinc-200 hover:text-zinc-800 focus-visible:bg-zinc-200 focus-visible:text-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus-visible:bg-zinc-700 dark:focus-visible:text-white' => ! $isMine && ! $pinned,
                                                ])
                                                data-test="{{ $pinned ? 'unpin-toggle' : 'pin-toggle' }}"
                                                @if ($pinned) data-test-active="true" @endif
                                            >
                                                @if ($pinned)
                                                    <flux:icon.bookmark-slash variant="micro" class="size-3.5" />
                                                @else
                                                    <flux:icon.bookmark variant="micro" class="size-3.5" />
                                                @endif
                                            </button>
                                        </flux:tooltip>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Composer --}}
        @can('comment', $conversation)
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <form
                    wire:submit="postReply"
                    x-data
                    x-on:keydown.enter="if ($event.metaKey || $event.ctrlKey) { $event.preventDefault(); $el.requestSubmit() }"
                >
                    <flux:composer wire:model="reply" label="Reply" label:sr-only placeholder="Write a reply…  (try mentioning Romans 8:31)">
                        <x-slot name="input">
                            <flux:editor
                                variant="borderless"
                                toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
                                class="**:data-[slot=content]:min-h-[100px]!"
                            />
                        </x-slot>

                        <x-slot name="footer">
                            <button
                                type="button"
                                wire:click="$toggle('replyIsPrayer')"
                                aria-pressed="{{ $replyIsPrayer ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex h-7 items-center gap-1.5 rounded-full border px-3 text-xs font-semibold transition-colors',
                                    'border-yellow-300 bg-yellow-50 text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-200' => $replyIsPrayer,
                                    'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => ! $replyIsPrayer,
                                ])
                                data-test="composer-prayer-toggle"
                                @if ($replyIsPrayer) data-test-active="true" @endif
                            >
                                <flux:icon.hand-raised variant="micro" />
                                {{ $replyIsPrayer ? 'Sending as prayer' : 'Mark as prayer' }}
                            </button>

                            <flux:tooltip content="Type @ to mention a member">
                                <button
                                    type="button"
                                    class="inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs font-semibold text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                                    data-test="composer-mention"
                                >
                                    <flux:icon.at-symbol variant="micro" />
                                    Mention
                                </button>
                            </flux:tooltip>

                            <div class="ms-auto flex items-center gap-2">
                                <span class="hidden items-center gap-1 text-xs text-zinc-400 sm:inline-flex" data-test="composer-shortcut-hint">
                                    <kbd class="rounded border border-zinc-200 bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">⌘</kbd>
                                    <kbd class="rounded border border-zinc-200 bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">↵</kbd>
                                    to send
                                </span>
                                <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane" wire:loading.attr="disabled">Send</flux:button>
                            </div>
                        </x-slot>
                    </flux:composer>
                    <flux:error name="reply" />
                </form>
            </div>
        @endcan
    </div>

    {{-- Members rail --}}
    <aside
        class="hidden w-[260px] shrink-0 flex-col overflow-auto border-l border-zinc-200 bg-zinc-50 px-5 py-6 lg:flex dark:border-zinc-700 dark:bg-zinc-900"
        aria-label="Members"
        data-test="members-rail"
    >
        <section class="mb-6" data-test="rail-files">
            <div class="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <span>Files · 0</span>
                <flux:tooltip content="Coming soon">
                    <flux:button size="xs" variant="ghost" icon="arrow-up-tray" disabled data-test="rail-files-add">Add</flux:button>
                </flux:tooltip>
            </div>
            <p class="px-1 py-2 text-xs text-zinc-500">
                No files yet. Drag a PDF or sheet music here to attach it to this conversation.
            </p>
        </section>

        <div class="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">
            Members · {{ $this->memberCount() }}
        </div>

        @if ($this->contributors->isNotEmpty())
            <section class="mb-6" data-test="rail-in-conversation">
                <div class="mb-2 flex items-center justify-between px-1 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    <span>In conversation</span>
                    <span>{{ $this->contributors->count() }}</span>
                </div>
                <div class="space-y-0.5">
                    @foreach ($this->contributors as $member)
                        <div class="flex items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800" wire:key="rail-contrib-{{ $member->id }}">
                            <flux:avatar size="xs" name="{{ $member->name }}" src="{{ $member->gravatar }}" color="auto" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold">{{ $member->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $member->pivot->role->label() }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($this->nonContributors->isNotEmpty())
            <section data-test="rail-not-yet-posted">
                <div class="mb-2 flex items-center justify-between px-1 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    <span>Not yet posted</span>
                    <span>{{ $this->nonContributors->count() }}</span>
                </div>
                <div class="space-y-0.5">
                    @foreach ($this->nonContributors as $member)
                        <div class="flex items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800" wire:key="rail-noncontrib-{{ $member->id }}">
                            <flux:avatar size="xs" name="{{ $member->name }}" src="{{ $member->gravatar }}" color="auto" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold">{{ $member->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $member->pivot->role->label() }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </aside>
</section>
