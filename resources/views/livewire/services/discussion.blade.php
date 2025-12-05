<?php

use App\Models\Service;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class() extends Component
{
    #[Reactive]
    public int $serviceId;

    public string $comment = '';

    public function getServiceProperty()
    {
        return Service::with('comments')->find($this->serviceId);
    }

    public function saveComment(): void
    {
        if (empty(trim(strip_tags($this->comment)))) {
            return;
        }

        $this->service->comment($this->comment);
        $this->reset('comment');
        unset($this->service);
    }

    public function react(int $commentId, string $reaction): void
    {
        $comment = $this->service->comments()->find($commentId);
        $comment->react($reaction);
        unset($this->service);
    }
};
?>

<div class="h-full">
    <div class="max-w-3xl space-y-2">
        @if($this->service->comments()->count() == 0)
        <div class="flex items-center justify-center">
            <flux:heading>No comments yet. Start the conversation!</flux:heading>
        </div>
        @else
            <div class="flex flex-col overflow-scroll w-full space-y-2
                **:[h1]:text-xl **:[h1]:font-semibold **:[h2]:text-lg **:[h2]:font-semibold **:[h3]:font-semibold
                **:[strong]:font-semibold **:[em]:italic **:[u]:underline **:[s]:line-through
                **:[ol]:list-decimal **:[ol]:ml-5 **:[ul]:list-disc **:[ul]:ml-5
                **:[a]:underline
                **:[blockquote]:border-l-4 **:[blockquote]:pl-2
                ">
            @php
                $prevUser = null;
            @endphp
            @foreach($this->service->comments as $comment)
                <div @class([
                    'text-left max-w-3/4 space-y-2',
                    'self-start' => !$comment->commentator?->is(auth()->user()),
                    'self-end'=> $comment->commentator?->is(auth()->user()),
                ])>
                    @if($comment->commentator && !$comment->commentator()->is($prevUser))
                    <div class="flex items-center space-x-2">
                        <flux:avatar size="xs" name="{{ $comment->commentator->name }}" color="auto" />
                        <flux:heading>{{ $comment->commentator->name }}</flux:heading>
                    </div>
                    @endif
                    <div @class([
                        'rounded-md p-2 space-y-2',
                        'bg-zinc-100' => !$comment->commentator?->is(auth()->user()),
                        'bg-accent text-white' => $comment->commentator?->is(auth()->user()),
                    ])>
                        {!! $comment->text !!}
                    </div>
                    <div class="flex flex-row-reverse items-center space-x-3 -mt-1.5">
                        <flux:dropdown hover position="{{ $comment->commentator?->is(auth()->user()) ? 'left' : 'right' }}" align="center">
                            <flux:button size="sm" variant="ghost" icon="face-smile" />
                            <flux:popover>
                                <div class="flex">
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '👍')">👍</flux:button>
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '✅')">✅</flux:button>
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '❤️')">❤️</flux:button>
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '🔥')">🔥</flux:button>
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '🙏')">🙏</flux:button>
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '❓')">❓</flux:button>
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '🤯')">🤯</flux:button>
                                    <flux:button size="sm" variant="ghost" square wire:click="react({{$comment->id }}, '🎉')">🎉</flux:button>
                                </div>
                            </flux:popover>
                        </flux:dropdown>
                        @foreach($comment->reactions->summary() as $reaction)
                            <span class="bg-zinc-100 rounded-full py-1 px-2">{{ $reaction['reaction'] }} {{ $reaction['count'] }}</span>
                        @endforeach
                    </div>
                </div>
                @php
                    $prevUser = $comment->commentator;
                @endphp
            @endforeach
            </div>
        @endif
    </div>
    <div class="max-w-3xl mt-6">
        <form wire:submit.prevent="saveComment">
            <flux:composer wire:model="comment" label="Comment" label:sr-only placeholder="Write your comment...">
                <x-slot name="input">
                    <flux:editor
                        variant="borderless"
                        toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
                        class="**:data-[slot=content]:min-h-[100px]!"
                    />
                </x-slot>
                <x-slot name="actionsLeading">

                </x-slot>
                <x-slot name="actionsTrailing">
                    <flux:button
                        type="submit"
                        variant="primary"
                        size="sm"
                        icon="paper-airplane"
                        wire:loading.attr="disabled"
                    />
                </x-slot>
            </flux:composer>
        </form>
    </div>
</div>
