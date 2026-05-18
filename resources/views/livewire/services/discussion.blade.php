<?php

use App\Models\Comment;
use App\Models\Service;
use App\Models\User;
use App\Services\ScriptureLinker;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Spatie\Comments\Actions\ResolveMentionsAutocompleteAction;
use Spatie\Comments\Support\Config;

new class extends Component {
    #[Reactive]
    public int $serviceId;

    public string $reply = '';

    public ?int $sheetCommentId = null;

    public ?int $commentToDeleteId = null;

    public ?int $editingCommentId = null;

    public string $editingText = '';

    #[Computed]
    public function service(): Service
    {
        /** @var Service */
        return Service::findOrFail($this->serviceId);
    }

    /** @return Collection<int, Comment> */
    #[Computed]
    public function comments(): Collection
    {
        /** @var Collection<int, Comment> */
        return $this->service->comments()
            ->with(['commentator', 'reactions.commentator'])
            ->orderBy('created_at')
            ->get();
    }

    /** @return BaseCollection<int|string, Collection<int, Comment>> */
    #[Computed]
    public function groupedComments(): BaseCollection
    {
        return $this->comments->groupBy(fn (Comment $comment): string => $comment->created_at->toDateString())->collect();
    }

    /** @return BaseCollection<int, array{user: User, hasPosted: bool}> */
    #[Computed]
    public function participants(): BaseCollection
    {
        $posterIds = $this->comments
            ->pluck('commentator.id')
            ->filter()
            ->unique()
            ->all();

        return $this->service->assignedUsers()
            ->map(fn (User $user): array => [
                'user' => $user,
                'hasPosted' => in_array($user->id, $posterIds, true),
            ])
            ->sortBy(fn (array $row): string => sprintf('%d-%s', $row['hasPosted'] ? 0 : 1, mb_strtolower($row['user']->name)))
            ->values()
            ->collect();
    }

    #[Computed]
    public function editingComment(): ?Comment
    {
        if ($this->editingCommentId === null) {
            return null;
        }

        return $this->comments->firstWhere('id', $this->editingCommentId);
    }

    #[Computed]
    public function sheetComment(): ?Comment
    {
        if ($this->sheetCommentId === null) {
            return null;
        }

        return $this->comments->firstWhere('id', $this->sheetCommentId);
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

    /** @return array<int, array{id: int, name: string, gravatar: string}> */
    public function mentionCandidates(string $query): array
    {
        /** @var ResolveMentionsAutocompleteAction $action */
        $action = app(config('comments.actions.resolve_mentions_autocomplete'));

        /** @var array<int, User> $candidates */
        $candidates = $action->execute($query, $this->service);

        return array_map(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'gravatar' => $user->gravatar,
        ], $candidates);
    }

    private function sanitizeMentions(string $html): string
    {
        $allowedIds = $this->service->assignedUsers()->pluck('id');

        return (string) preg_replace_callback(
            '/<(\w+)(\s+[^>]*?)data-mention="([^"]+)"([^>]*?)>(.*?)<\/\1>/s',
            function (array $match) use ($allowedIds): string {
                if ($allowedIds->contains((int) $match[3])) {
                    return $match[0];
                }

                return "<{$match[1]}{$match[2]}{$match[4]}>{$match[5]}</{$match[1]}>";
            },
            $html,
        );
    }

    public function postReply(): void
    {
        $hasText = trim(strip_tags($this->reply)) !== '';

        if (! $hasText) {
            $this->addError('reply', 'Write a message.');

            return;
        }

        $text = $this->sanitizeMentions(trim($this->reply));

        $this->service->comment($text, Auth::user());

        $this->reset('reply');
        unset($this->comments);
    }

    public function react(int $commentId, string $reaction): void
    {
        abort_unless(in_array($reaction, Config::allowedReactions(), true), 422);

        /** @var Comment $comment */
        $comment = $this->service->comments()->findOrFail($commentId);
        $user = Auth::user();

        if ($comment->findReaction($reaction, $user)) {
            $comment->deleteReaction($reaction, $user);
        } else {
            $comment->react($reaction, $user);
        }

        unset($this->comments);
    }

    public function startEditing(int $commentId): void
    {
        /** @var Comment $comment */
        $comment = $this->service->comments()->findOrFail($commentId);

        $this->authorize('update', $comment);

        $this->editingCommentId = $comment->id;
        $this->editingText = $comment->original_text;

        $this->sheetCommentId = null;
        Flux::modal('service-message-actions')->close();

        unset($this->editingComment);
    }

    public function cancelEditing(): void
    {
        $this->reset('editingCommentId', 'editingText');
        unset($this->editingComment);
    }

    public function saveEdit(): void
    {
        $comment = $this->editingComment;

        if (! $comment instanceof Comment) {
            return;
        }

        $this->authorize('update', $comment);

        $newHtml = $this->sanitizeMentions(trim($this->editingText));
        $plainText = trim(strip_tags($newHtml));

        if ($plainText === '') {
            $this->addError('editingText', 'Write a message.');

            return;
        }

        $previousText = $comment->original_text;

        $comment->original_text = $newHtml;
        $comment->save();

        if ($newHtml !== $previousText) {
            $comment->markAsEdited();
        }

        $this->reset('editingCommentId', 'editingText');
        unset($this->comments, $this->editingComment);

        Flux::toast(variant: 'success', text: 'Message updated.');
    }

    public function confirmDeleteComment(int $commentId): void
    {
        /** @var Comment $comment */
        $comment = $this->service->comments()->findOrFail($commentId);

        $this->authorize('delete', $comment);

        $this->commentToDeleteId = $commentId;

        Flux::modal('service-message-actions')->close();
        Flux::modal('service-delete-comment')->show();
    }

    public function deleteComment(): void
    {
        if ($this->commentToDeleteId === null) {
            return;
        }

        /** @var Comment $comment */
        $comment = $this->service->comments()->findOrFail($this->commentToDeleteId);

        $this->authorize('delete', $comment);

        $comment->delete();

        if ($this->sheetCommentId === $comment->id) {
            $this->sheetCommentId = null;
        }

        $this->commentToDeleteId = null;

        unset($this->comments);

        Flux::modal('service-delete-comment')->close();
        Flux::toast(variant: 'success', text: 'Message deleted.');
    }

    public function openActions(int $commentId): void
    {
        $this->sheetCommentId = $commentId;

        Flux::modal('service-message-actions')->show();
    }

    public function closeActions(): void
    {
        $this->sheetCommentId = null;
    }
};
?>

<section data-test="service-discussion">
    @php($currentUser = Auth::user())
    @php($quickReactions = Config::allowedReactions())

    <div
        class="flex w-full overflow-hidden"
        style="height: min(calc(100vh - 22rem), 48rem); min-height: 28rem;"
    >
        {{-- Conversation column (messages + composer) --}}
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
        {{-- Messages --}}
        <div class="min-h-0 flex-1 overflow-auto px-4 py-3 lg:px-6">
            @if ($this->comments->isEmpty())
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center" data-test="empty-state">
                    <flux:icon.chat-bubble-left-right class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">No messages yet</flux:heading>
                    <flux:subheading class="mt-1">Kick things off — your reply will start the thread.</flux:subheading>
                </div>
            @endif

            @foreach ($this->groupedComments as $dateString => $dayComments)
                <x-conversation.day-divider :label="$this->dayLabel($dateString)" />

                @foreach ($dayComments as $comment)
                    @php($isMine = $comment->commentator?->is($currentUser))

                    <div
                        wire:key="comment-{{ $comment->id }}"
                        @class([
                            'group/row relative mt-1 flex gap-2.5 rounded-md border-l-2 px-2.5 py-3 transition-colors lg:gap-3 lg:px-3',
                            'border-transparent hover:bg-zinc-50 dark:hover:bg-zinc-900/60' => ! $isMine,
                            'border-accent bg-accent/5 hover:bg-accent/10' => $isMine,
                        ])
                        data-test="message-row"
                        @if ($isMine) data-test-mine="true" @endif
                    >
                        <flux:avatar size="sm" class="lg:hidden" name="{{ $comment->commentator?->name }}" src="{{ $comment->commentator?->gravatar }}" color="auto" />
                        <flux:avatar size="md" class="hidden lg:flex" name="{{ $comment->commentator?->name }}" src="{{ $comment->commentator?->gravatar }}" color="auto" />

                        <div class="min-w-0 flex-1 pr-8 lg:pr-0">
                            {{-- Header --}}
                            <div class="flex flex-col gap-y-0.5 lg:flex-row lg:flex-wrap lg:items-baseline lg:gap-x-2 lg:gap-y-1">
                                <span @class([
                                    'text-sm font-bold',
                                    'text-accent' => $isMine,
                                    'text-zinc-900 dark:text-white' => ! $isMine,
                                ])>
                                    {{ $comment->commentator?->name ?? 'Unknown' }}@if ($isMine) <span class="font-normal text-zinc-500">(you)</span>@endif
                                </span>
                                <div class="flex items-baseline gap-x-1.5 text-xs text-zinc-500">
                                    <span class="text-zinc-400">{{ $comment->created_at->diffForHumans() }}</span>
                                    @if ($comment->edited_at)
                                        <span class="text-zinc-400" title="Edited {{ $comment->edited_at->diffForHumans() }}" data-test="edited-indicator">(edited)</span>
                                    @endif
                                </div>
                            </div>

                            @if ($editingCommentId === $comment->id)
                                <div class="mt-2" data-test="inline-edit-composer">
                                    <x-conversation-composer
                                        editor-model="editingText"
                                        :allow-prayer="false"
                                        :allow-mentions="true"
                                        submit-action="saveEdit"
                                        submit-label="Save changes"
                                        cancel-action="cancelEditing"
                                        :editing="true"
                                        test-prefix="edit-composer"
                                    />
                                </div>
                            @else
                                {{-- Body --}}
                                <div class="mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-200
                                    **:[h1]:text-xl **:[h1]:font-semibold **:[h2]:text-lg **:[h2]:font-semibold **:[h3]:font-semibold
                                    **:[strong]:font-semibold **:[em]:italic **:[u]:underline **:[s]:line-through
                                    **:[ol]:list-decimal **:[ol]:ml-5 **:[ul]:list-disc **:[ul]:ml-5
                                    **:[a]:underline
                                    **:[blockquote]:border-l-4 **:[blockquote]:pl-2
                                    **:[h1]:not-first:mt-3 **:[h2]:not-first:mt-3 **:[h3]:not-first:mt-3
                                    **:[ul]:not-first:mt-3 **:[ol]:not-first:mt-3
                                    **:[blockquote]:not-first:mt-3
                                    **:[p]:not-first:mt-3
                                    **:[h1+p]:mt-0 **:[h2+p]:mt-0 **:[h3+p]:mt-0">
                                    {!! app(ScriptureLinker::class)->linkify($comment->text) !!}
                                </div>

                                {{-- Reactions row --}}
                                @php($summary = $comment->reactions->summary($currentUser))
                                @if ($summary->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        @foreach ($summary as $reaction)
                                            @php($mine = (bool) ($reaction['commentator_reacted'] ?? false))
                                            @php($reactorNames = $comment->reactions
                                                ->where('reaction', $reaction['reaction'])
                                                ->map(fn ($r) => $currentUser
                                                    && $r->commentator_id === $currentUser->getKey()
                                                    && $r->commentator_type === $currentUser->getMorphClass()
                                                        ? 'You'
                                                        : $r->commentator?->name)
                                                ->filter()
                                                ->sortBy(fn ($name) => $name === 'You' ? 0 : 1)
                                                ->values()
                                                ->implode(', '))
                                            <flux:tooltip content="{{ $reactorNames }}">
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
                                            </flux:tooltip>
                                        @endforeach

                                        {{-- Mobile: opens the action sheet --}}
                                        <button
                                            type="button"
                                            wire:click="openActions({{ $comment->id }})"
                                            class="inline-flex h-6 items-center justify-center rounded-full border border-dashed border-zinc-300 px-2 text-xs text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 lg:hidden dark:border-zinc-600 dark:hover:bg-zinc-800"
                                            aria-label="Add reaction"
                                            data-test="reaction-picker-trigger-mobile"
                                        >
                                            <flux:icon.face-smile variant="micro" />
                                        </button>

                                        {{-- Desktop: inline emoji popover --}}
                                        <flux:dropdown align="start" class="hidden lg:inline-flex">
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
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Mobile action sheet trigger --}}
                        @if ($editingCommentId !== $comment->id)
                            <button
                                type="button"
                                wire:click="openActions({{ $comment->id }})"
                                aria-label="Message actions"
                                @class([
                                    'absolute right-2 top-2 grid size-8 place-items-center rounded-md transition-colors duration-100 hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800',
                                    'text-accent' => $isMine,
                                    'text-zinc-500 dark:text-zinc-400' => ! $isMine,
                                ])
                                data-test="message-actions-trigger"
                            >
                                <flux:icon.ellipsis-horizontal variant="micro" class="size-4" />
                            </button>
                        @endif

                        {{-- Hover toolbar (desktop) --}}
                        @if ($editingCommentId !== $comment->id)
                            <div
                                class="absolute right-3 top-2.5 z-10 hidden gap-0.5 group-hover/row:flex group-focus-within/row:flex group-has-[[data-flux-popover][data-open]]/row:flex"
                                data-test="hover-toolbar"
                                role="toolbar"
                                aria-label="Message actions"
                            >
                                @can('update', $comment)
                                    <flux:tooltip content="Edit message">
                                        <button
                                            type="button"
                                            wire:click="startEditing({{ $comment->id }})"
                                            aria-label="Edit message"
                                            @class([
                                                'flex size-[26px] cursor-pointer items-center justify-center rounded-md transition-colors duration-[120ms] focus-visible:outline-none',
                                                'text-accent hover:bg-accent/20 focus-visible:bg-accent/20' => $isMine,
                                                'text-zinc-500 hover:bg-zinc-200 hover:text-zinc-800 focus-visible:bg-zinc-200 focus-visible:text-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus-visible:bg-zinc-700 dark:focus-visible:text-white' => ! $isMine,
                                            ])
                                            data-test="edit-toggle"
                                        >
                                            <flux:icon.pencil-square variant="micro" class="size-3.5" />
                                        </button>
                                    </flux:tooltip>
                                @endcan

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

                                @can('delete', $comment)
                                    <flux:tooltip content="Delete message">
                                        <button
                                            type="button"
                                            wire:click="confirmDeleteComment({{ $comment->id }})"
                                            aria-label="Delete message"
                                            class="flex size-[26px] cursor-pointer items-center justify-center rounded-md text-zinc-500 transition-colors duration-[120ms] hover:bg-red-50 hover:text-red-600 focus-visible:bg-red-50 focus-visible:text-red-600 focus-visible:outline-none dark:hover:bg-red-950/40 dark:hover:text-red-400 dark:focus-visible:bg-red-950/40 dark:focus-visible:text-red-400"
                                            data-test="delete-toggle"
                                        >
                                            <flux:icon.trash variant="micro" class="size-3.5" />
                                        </button>
                                    </flux:tooltip>
                                @endcan
                            </div>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- Composer --}}
        @if (! $editingCommentId)
            <div
                class="border-t border-zinc-200 px-3.5 py-2.5 lg:px-6 lg:py-4 dark:border-zinc-700"
                x-data="{ collapse() {} }"
            >
                <x-conversation-composer
                    editor-model="reply"
                    :allow-prayer="false"
                    :allow-mentions="true"
                    submit-action="postReply"
                />
            </div>
        @endif
        </div>

        {{-- Participants sidebar (lg+ only) --}}
        <aside
            class="hidden w-64 shrink-0 flex-col overflow-hidden border-l border-zinc-200 lg:flex dark:border-zinc-700"
            data-test="discussion-participants"
        >
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="sm">In this discussion</flux:heading>
                <flux:subheading size="sm">{{ $this->participants->count() }} {{ Str::plural('person', $this->participants->count()) }}</flux:subheading>
            </div>

            <div class="min-h-0 flex-1 overflow-auto px-2 py-2">
                @forelse ($this->participants as $row)
                    @php($user = $row['user'])
                    <div
                        @class([
                            'flex items-center gap-2.5 rounded-md px-2 py-1.5',
                            'opacity-60' => ! $row['hasPosted'],
                        ])
                        data-test="participant-row"
                        data-has-posted="{{ $row['hasPosted'] ? 'true' : 'false' }}"
                    >
                        <flux:avatar :name="$user->name" :src="$user->gravatarUrl()" size="xs" color="auto" />
                        <span class="min-w-0 flex-1 truncate text-sm text-zinc-900 dark:text-zinc-100">{{ $user->name }}</span>
                        @if ($row['hasPosted'])
                            <span
                                class="size-1.5 shrink-0 rounded-full bg-emerald-500"
                                title="Has posted in this discussion"
                                aria-label="Has posted in this discussion"
                            ></span>
                        @endif
                    </div>
                @empty
                    <div class="px-3 py-4 text-xs text-zinc-500">
                        No assigned participants yet.
                    </div>
                @endforelse
            </div>
        </aside>
    </div>

    {{-- Mobile action sheet --}}
    @php($sheetComment = $this->sheetComment)
    <flux:modal
        name="service-message-actions"
        flyout
        position="bottom"
        variant="bare"
        class="lg:hidden"
        x-on:close="$wire.closeActions()"
    >
        @if ($sheetComment)
            @php($myReactions = $sheetComment->reactions
                ->filter(fn ($r) => $currentUser
                    && $r->commentator_id === $currentUser->getKey()
                    && $r->commentator_type === $currentUser->getMorphClass())
                ->pluck('reaction')
                ->all())
            <div
                role="dialog"
                aria-label="Message actions"
                class="rounded-t-2xl bg-white pb-[max(0.75rem,env(safe-area-inset-bottom))] dark:bg-zinc-900"
                data-test="message-actions-sheet"
            >
                <div class="mx-auto mt-2.5 h-1 w-10 rounded-full bg-zinc-300 dark:bg-zinc-600"></div>

                <div class="px-4 pb-3 pt-2.5">
                    <div class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                        {{ $sheetComment->commentator?->name ?? 'Unknown' }}
                        · {{ $sheetComment->created_at->diffForHumans() }}
                    </div>
                    <div class="mt-1 line-clamp-2 text-sm text-zinc-700 dark:text-zinc-200">
                        {{ Str::limit(html_entity_decode(strip_tags($sheetComment->text), ENT_QUOTES | ENT_HTML5), 180) }}
                    </div>
                </div>

                <div class="flex gap-1.5 border-t border-zinc-200 px-3.5 pb-3 pt-3.5 dark:border-zinc-700">
                    @foreach ($quickReactions as $allowedReaction)
                        @php($mine = in_array($allowedReaction, $myReactions, true))
                        <button
                            type="button"
                            wire:click="react({{ $sheetComment->id }}, '{{ $allowedReaction }}')"
                            x-on:click="$flux.modal('service-message-actions').close()"
                            @class([
                                'flex h-12 flex-1 items-center justify-center rounded-xl border text-[1.375rem] leading-none transition-colors',
                                'border-accent bg-accent/10' => $mine,
                                'border-zinc-200 bg-white hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700' => ! $mine,
                            ])
                            data-test="sheet-reaction"
                            @if ($mine) data-test-mine="true" @endif
                        >{{ $allowedReaction }}</button>
                    @endforeach
                </div>

                <div class="border-t border-zinc-200 dark:border-zinc-700">
                    @can('update', $sheetComment)
                        <button
                            type="button"
                            wire:click="startEditing({{ $sheetComment->id }})"
                            x-on:click="$flux.modal('service-message-actions').close()"
                            class="flex w-full items-center gap-3.5 border-b border-zinc-100 px-5 py-3.5 text-left text-[15px] font-medium dark:border-zinc-800"
                            data-test="sheet-edit"
                        >
                            <span class="grid size-8 shrink-0 place-items-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon.pencil-square variant="micro" />
                            </span>
                            Edit message
                        </button>
                    @endcan

                    <button
                        type="button"
                        x-on:click="navigator.clipboard?.writeText($el.dataset.copyText); $flux.modal('service-message-actions').close()"
                        data-copy-text="{{ html_entity_decode(strip_tags($sheetComment->text), ENT_QUOTES | ENT_HTML5) }}"
                        class="flex w-full items-center gap-3.5 border-b border-zinc-100 px-5 py-3.5 text-left text-[15px] font-medium dark:border-zinc-800"
                        data-test="sheet-copy"
                    >
                        <span class="grid size-8 shrink-0 place-items-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <flux:icon.clipboard-document variant="micro" />
                        </span>
                        Copy text
                    </button>

                    @can('delete', $sheetComment)
                        <button
                            type="button"
                            wire:click="confirmDeleteComment({{ $sheetComment->id }})"
                            class="flex w-full items-center gap-3.5 border-b border-zinc-100 px-5 py-3.5 text-left text-[15px] font-medium text-red-600 dark:border-zinc-800 dark:text-red-400"
                            data-test="sheet-delete"
                        >
                            <span class="grid size-8 shrink-0 place-items-center rounded-md bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400">
                                <flux:icon.trash variant="micro" />
                            </span>
                            Delete message
                        </button>
                    @endcan
                </div>

                <div class="px-3.5 pb-1 pt-2.5">
                    <flux:modal.close>
                        <button
                            type="button"
                            class="h-12 w-full rounded-xl border border-zinc-200 bg-white text-[15px] font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                            data-test="sheet-cancel"
                        >
                            Cancel
                        </button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="service-delete-comment" class="min-w-[22rem]">
        <form wire:submit="deleteComment" class="space-y-6">
            <div>
                <flux:heading size="lg">Delete message?</flux:heading>
                <flux:subheading>This permanently removes the message and its reactions. This cannot be undone.</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" data-test="confirm-delete-comment">Delete message</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
