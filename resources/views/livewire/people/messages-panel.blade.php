<?php

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Person;
use App\Models\User;
use App\Services\ScriptureLinker;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Person $person;

    public string $reply = '';

    public function mount(): void
    {
        $conversation = $this->conversation;

        if ($conversation !== null) {
            $conversation->markReadFor($this->user());
        }
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * The 1:1 conversation between the current user and this person, looked up
     * read-only. Returns null until the first message creates it, so simply
     * viewing the panel never writes a phantom conversation.
     */
    #[Computed]
    public function conversation(): ?Conversation
    {
        $other = $this->person->user;

        if ($other === null) {
            return null;
        }

        $key = Group::directKeyFor([$this->user()->id, $other->id]);

        return Group::query()->direct()->where('direct_key', $key)->first()
            ?->directConversation()->first();
    }

    /** @return EloquentCollection<int, Comment> */
    #[Computed]
    public function messages(): EloquentCollection
    {
        $conversation = $this->conversation;

        if ($conversation === null) {
            /** @var EloquentCollection<int, Comment> */
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

    public function send(): void
    {
        $other = $this->person->user;

        if ($other === null) {
            return;
        }

        if (trim(strip_tags($this->reply)) === '') {
            $this->addError('reply', 'Write a message.');

            return;
        }

        $user = $this->user();
        $group = Group::findOrCreateDirect($user, [$other->id]);

        /** @var Conversation $conversation */
        $conversation = $group->directConversation()->firstOrFail();

        $this->authorize('comment', $conversation);

        $conversation->postComment(trim($this->reply), $user);
        $conversation->markReadFor($user);

        $this->reply = '';
        unset($this->conversation, $this->messages, $this->groupedMessages);
        $this->dispatch('messages-unread-updated');
    }

    public function handleIncoming(?int $conversationId = null): void
    {
        $conversation = $this->conversation;

        if ($conversation === null) {
            return;
        }

        if ($conversationId !== null && $conversationId !== $conversation->id) {
            return;
        }

        $conversation->markReadFor($this->user());
        unset($this->conversation, $this->messages, $this->groupedMessages);
        $this->dispatch('messages-unread-updated');
    }
}; ?>

<flux:card class="overflow-hidden !p-0">
    <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
        <flux:icon icon="chat-bubble-left-right" class="size-5 text-emerald-600 dark:text-emerald-400" />
        <flux:heading size="lg">Messages</flux:heading>
        <flux:text size="sm" class="text-zinc-500">Direct conversation with {{ $person->first_name }}</flux:text>
    </div>

    <div class="max-h-[340px] space-y-4 overflow-y-auto bg-zinc-50 px-5 py-4 dark:bg-zinc-800/50" data-test="person-dm-thread">
        @forelse ($this->groupedMessages as $dateString => $dayMessages)
            <x-conversation.day-divider :label="$this->dayLabel($dateString)" />

            @foreach ($dayMessages as $comment)
                @php($author = $comment->commentator)
                @php($isMine = $author instanceof User && $author->id === $this->user()->id)

                <div @class(['flex flex-col gap-1', 'items-end' => $isMine, 'items-start' => ! $isMine]) wire:key="msg-{{ $comment->id }}" data-test="message">
                    <div @class([
                        'max-w-[80%] break-words rounded-xl px-3.5 py-2.5 text-[13px] leading-relaxed',
                        'rounded-tr-sm bg-accent text-white' => $isMine,
                        'rounded-tl-sm border border-zinc-200 bg-white text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100' => ! $isMine,
                    ])>
                        <div class="**:[a]:underline **:[strong]:font-semibold **:[em]:italic **:[ul]:ml-5 **:[ul]:list-disc **:[ol]:ml-5 **:[ol]:list-decimal **:[p]:not-first:mt-2">
                            {!! app(ScriptureLinker::class)->linkify($comment->text) !!}
                        </div>
                    </div>
                    <span @class(['text-[10px] text-zinc-400', 'text-right' => $isMine, 'text-left' => ! $isMine])>{{ $comment->created_at->format('g:i A') }}</span>
                </div>
            @endforeach
        @empty
            <div class="py-6 text-center text-[13px] text-zinc-500 dark:text-zinc-400" data-test="person-dm-empty">
                No messages yet. Start the conversation below.
            </div>
        @endforelse
    </div>

    <div class="border-t border-zinc-200 px-5 pb-4 pt-3 dark:border-zinc-700">
        <x-conversation-composer
            editor-model="reply"
            submit-action="send"
            submit-label="Send"
            :allow-prayer="false"
            :allow-mentions="false"
            test-prefix="person-dm"
        />
    </div>

    @script
    <script>
        const handler = (event) => {
            const detail = event.detail ?? {};

            if (detail.is_direct || detail.conversation_id) {
                $wire.handleIncoming(detail.conversation_id ?? null);
            }
        };

        window.addEventListener('stave:notification', handler);
        $cleanup(() => window.removeEventListener('stave:notification', handler));
    </script>
    @endscript
</flux:card>
