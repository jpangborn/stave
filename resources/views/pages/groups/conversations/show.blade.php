<?php

use App\Models\Conversation;
use App\Models\Group;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Comments\Models\Comment;
use Spatie\Comments\Support\Config;

new class extends Component {
    public Group $group;
    public Conversation $conversation;

    public string $reply = '';

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

        $this->conversation->postComment($validated['reply'], Auth::user());

        $this->reset('reply');
        unset($this->comments);
    }

    public function react(int $commentId, string $reaction): void
    {
        $this->authorize('comment', $this->conversation);

        abort_unless(in_array($reaction, Config::allowedReactions(), true), 422);

        /** @var Comment $comment */
        $comment = $this->conversation->comments()->findOrFail($commentId);
        $comment->react($reaction);

        unset($this->comments);
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

<section class="w-full">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:button :href="route('groups.show', $group)" variant="ghost" size="sm" icon="arrow-left" wire:navigate>
                Back to {{ $group->name }}
            </flux:button>
            <flux:heading size="xl" level="1" class="mt-2">{{ $conversation->title }}</flux:heading>
            <flux:subheading>
                Started by {{ $conversation->creator?->name ?? 'Unknown' }} {{ $conversation->created_at->diffForHumans() }}
            </flux:subheading>
        </div>

        @can('delete', $conversation)
            <flux:button wire:click="deleteConversation" variant="danger" size="sm" icon="trash"
                wire:confirm="Delete this conversation and all its messages?">
                Delete
            </flux:button>
        @endcan
    </div>

    <div class="mt-8 max-w-3xl">
        <div class="flex flex-col w-full space-y-2
            **:[h1]:text-xl **:[h1]:font-semibold **:[h2]:text-lg **:[h2]:font-semibold **:[h3]:font-semibold
            **:[strong]:font-semibold **:[em]:italic **:[u]:underline **:[s]:line-through
            **:[ol]:list-decimal **:[ol]:ml-5 **:[ul]:list-disc **:[ul]:ml-5
            **:[a]:underline
            **:[blockquote]:border-l-4 **:[blockquote]:pl-2">
            @php($prevUser = null)

            @foreach ($this->comments as $comment)
                <div wire:key="comment-{{ $comment->id }}" @class([
                    'text-left max-w-3/4 space-y-2',
                    'self-start' => ! $comment->commentator?->is(Auth::user()),
                    'self-end' => $comment->commentator?->is(Auth::user()),
                ])>
                    @if ($comment->commentator?->isNot($prevUser))
                        <div class="flex items-center space-x-2">
                            <flux:avatar size="xs" name="{{ $comment->commentator?->name }}" color="auto" />
                            <flux:heading>{{ $comment->commentator?->name ?? 'Unknown' }}</flux:heading>
                            <flux:subheading class="text-xs">{{ $comment->created_at->diffForHumans() }}</flux:subheading>
                        </div>
                    @endif

                    <div @class([
                        'rounded-md p-2 space-y-2',
                        'bg-zinc-100 dark:bg-zinc-800' => ! $comment->commentator?->is(Auth::user()),
                        'bg-accent text-white' => $comment->commentator?->is(Auth::user()),
                    ])>
                        {!! $comment->text !!}
                    </div>
                    <div class="flex flex-row-reverse items-center space-x-3 -mt-1.5">
                        @can('comment', $conversation)
                            <flux:dropdown hover position="{{ $comment->commentator?->is(Auth::user()) ? 'left' : 'right' }}" align="center">
                                <flux:button size="sm" variant="ghost" icon="face-smile" />
                                <flux:popover>
                                    <div class="flex">
                                        @foreach (Config::allowedReactions() as $allowedReaction)
                                            <flux:button size="sm" variant="ghost" square wire:click="react({{ $comment->id }}, '{{ $allowedReaction }}')">{{ $allowedReaction }}</flux:button>
                                        @endforeach
                                    </div>
                                </flux:popover>
                            </flux:dropdown>
                        @endcan
                        @foreach ($comment->reactions->summary() as $reaction)
                            <span wire:key="reaction-{{ $comment->id }}-{{ $reaction['reaction'] }}" class="bg-zinc-100 dark:bg-zinc-800 rounded-full py-1 px-2">{{ $reaction['reaction'] }} {{ $reaction['count'] }}</span>
                        @endforeach
                    </div>
                </div>
                @php($prevUser = $comment->commentator)
            @endforeach
        </div>

        @can('comment', $conversation)
            <form wire:submit="postReply" class="mt-6">
                <flux:composer wire:model="reply" label="Reply" label:sr-only placeholder="Write a reply...">
                    <x-slot name="input">
                        <flux:editor
                            variant="borderless"
                            toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
                            class="**:data-[slot=content]:min-h-[100px]!"
                        />
                    </x-slot>
                    <x-slot name="actionsTrailing">
                        <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane" wire:loading.attr="disabled" />
                    </x-slot>
                </flux:composer>
                <flux:error name="reply" />
            </form>
        @endcan
    </div>
</section>
