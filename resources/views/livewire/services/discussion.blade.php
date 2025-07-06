<?php

use App\Models\Service;
use App\Models\User;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public int $serviceId;

    public string $comment;

    public function getServiceProperty()
    {
        return Service::with("comments")->find($this->serviceId);
    }

    public function saveComment()
    {
        $this->service->comment($this->comment);
        $this->reset("comment");
        unset($this->service);
    }

    public function saveProxyComment()
    {
        $user = User::where("email", "test@example.com")->first();
        $this->service->comment($this->comment, $user);
        $this->reset("comment");
        unset($this->service);
    }

    public function react(int $commentId, string $reaction)
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
                    'self-start' => !$comment->commentator()->is(auth()->user()),
                    'self-end'=> $comment->commentator()->is(auth()->user()),
                ])>
                    @if(!$comment->commentator()->is($prevUser))
                    <div class="flex items-center space-x-2">
                        <flux:avatar size="xs" name="{{ $comment->commentator->name }}" color="auto" />
                        <flux:heading>{{ $comment->commentator->name }}</flux:heading>
                    </div>
                    @endif
                    <div @class([
                        'rounded-md p-2 space-y-2',
                        'bg-zinc-100' => !$comment->commentator()->is(auth()->user()),
                        'bg-accent text-white' => $comment->commentator()->is(auth()->user()),
                    ])>
                        {!! $comment->text !!}
                    </div>
                    <div class="flex flex-row-reverse items-center space-x-2 -mt-2">
                        <flux:dropdown hover position="{{ $comment->commentator()->is(auth()->user()) ? 'left' : 'right' }}" align="center">
                            <flux:button size="sm" variant="ghost" icon="face-smile" />
                            <flux:popover>
                                <div class="flex space-x-1">
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
                            <span>{{ $reaction['reaction'] }} {{ $reaction['count'] }}</span>
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
    <div class="max-w-3xl space-y-2 mt-6">
        <flux:editor wire:model="comment" toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo" class="**:data-[slot=content]:min-h-[100px]!" />
        <div class="flex space-x-2">
            <flux:spacer />
            <flux:button variant="primary" wire:click="saveProxyComment">Proxy Comment</flux:button>
            <flux:button variant="primary" wire:click="saveComment">Comment</flux:button>
        </div>
    </div>
</div>
