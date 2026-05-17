<?php

use App\Models\Conversation;
use App\Models\Group;
use App\Notifications\NewConversationNotification;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Group $group;

    public string $title = '';

    public string $body = '';

    public bool $allowReplies = true;

    public bool $pinOnPost = false;

    public function mount(Group $group): void
    {
        $this->authorize('create', [Conversation::class, $group]);

        $this->group = $group;
    }

    #[Computed]
    public function isLeader(): bool
    {
        return $this->group->hasLeader(Auth::user());
    }

    #[Computed]
    public function leaders(): Collection
    {
        return $this->group->leaders()->get();
    }

    public function save(): void
    {
        $this->authorize('create', [Conversation::class, $this->group]);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'allowReplies' => ['boolean'],
            'pinOnPost' => ['boolean'],
        ]);

        if (trim(strip_tags($validated['body'])) === '') {
            $this->addError('body', 'Please write an opening message.');

            return;
        }

        $shouldPin = $this->pinOnPost && $this->isLeader;

        $conversation = DB::transaction(function () use ($validated, $shouldPin): Conversation {
            $conversation = $this->group->conversations()->create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'allow_replies' => (bool) $validated['allowReplies'],
            ]);

            $conversation->postComment($validated['body'], Auth::user());

            if ($shouldPin) {
                $conversation->pin(Auth::user());
            }

            return $conversation;
        });

        $this->notifyMembers($conversation);

        Flux::toast(variant: 'success', text: 'Conversation started.');

        $this->redirect(route('groups.conversations.show', [
            'group' => $this->group,
            'conversation' => $conversation,
        ]), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('groups.show', $this->group), navigate: true);
    }

    private function notifyMembers(Conversation $conversation): void
    {
        $recipients = $this->group->members()
            ->where('users.id', '!=', Auth::id())
            ->get();

        Notification::send($recipients, new NewConversationNotification($conversation, Auth::user()));
    }
};
?>

<section
    class="mx-auto w-full max-w-[980px] space-y-7 pb-16"
    x-data
    @keydown.escape.window="$wire.cancel()"
    @keydown.meta.enter.window.prevent="$wire.save()"
    @keydown.ctrl.enter.window.prevent="$wire.save()"
>
    {{-- Header --}}
    <header class="space-y-2">
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
            <flux:heading size="xl" level="1">Start a Conversation</flux:heading>
        </div>
        <flux:subheading>
            Post a new conversation in <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $group->name }}</span>.
        </flux:subheading>
    </header>

    <form wire:submit="save" class="space-y-6">
        {{-- Title --}}
        <flux:field>
            <flux:label badge="Required">Title</flux:label>
            <flux:input
                type="text"
                name="title"
                wire:model="title"
                placeholder="e.g. Sunday Service Planning"
                autofocus
            />
            <flux:error name="title" />
        </flux:field>

        {{-- Message --}}
        <flux:field>
            <flux:label badge="Required">Message</flux:label>
            <flux:editor
                wire:model="body"
                toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
                class="**:data-[slot=content]:min-h-[200px]"
            />
            <flux:error name="body" />
        </flux:field>

        {{-- Settings --}}
        <div class="space-y-2" data-test="settings-card">
            <flux:label>Settings</flux:label>
            <div
                @class([
                    'overflow-hidden rounded-xl border transition-colors duration-200',
                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $allowReplies,
                    'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20' => ! $allowReplies,
                ])
            >
                {{-- Allow replies row --}}
                <div class="flex items-start gap-3.5 p-4" data-test="allow-replies-row">
                    <div
                        @class([
                            'grid size-7 shrink-0 place-items-center rounded-lg transition-colors duration-200',
                            'bg-accent/10 text-accent' => $allowReplies,
                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-800/50 dark:text-yellow-200' => ! $allowReplies,
                        ])
                    >
                        @if ($allowReplies)
                            <flux:icon.chat-bubble-left-right variant="micro" />
                        @else
                            <flux:icon.lock-closed variant="micro" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Allow replies</div>
                        <div class="mt-0.5 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            @if ($allowReplies)
                                Anyone in this group can post replies.
                            @else
                                Replies are turned off. Only group leaders will be able to post in this conversation.
                            @endif
                        </div>
                    </div>
                    <div class="shrink-0">
                        <flux:switch wire:model.live="allowReplies" data-test="allow-replies-switch" />
                    </div>
                </div>

                {{-- Leader preview --}}
                @if (! $allowReplies)
                    <div
                        class="border-t border-yellow-300 bg-yellow-100/60 px-4 py-3 pl-[3.75rem] dark:border-yellow-700 dark:bg-yellow-900/30"
                        data-test="leader-preview"
                    >
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-yellow-800 dark:text-yellow-200">
                            Leaders who can post · {{ $this->leaders->count() }}
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @forelse ($this->leaders as $leader)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-yellow-300 bg-white py-0.5 pl-0.5 pr-2.5 dark:border-yellow-700 dark:bg-zinc-900">
                                    <flux:avatar
                                        size="xs"
                                        name="{{ $leader->name }}"
                                        src="{{ $leader->gravatar }}"
                                        color="auto"
                                    />
                                    <span class="text-xs font-semibold text-yellow-900 dark:text-yellow-100">{{ $leader->name }}</span>
                                    <span class="text-[11px] text-yellow-700 dark:text-yellow-300">· Leader</span>
                                </span>
                            @empty
                                <span class="text-xs text-yellow-800 dark:text-yellow-200">
                                    No leaders are assigned to this group yet.
                                </span>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- Pin to top — leaders only --}}
                @if ($this->isLeader)
                    <div
                        @class([
                            'flex items-start gap-3.5 border-t p-4',
                            'border-zinc-200 dark:border-zinc-700' => $allowReplies,
                            'border-yellow-300 dark:border-yellow-700' => ! $allowReplies,
                        ])
                        data-test="pin-row"
                    >
                        <div class="grid size-7 shrink-0 place-items-center rounded-lg bg-accent/10 text-accent">
                            <flux:icon.bookmark variant="micro" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Pin to top</div>
                            <div class="mt-0.5 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Keep this conversation at the top of the group for everyone.
                            </div>
                        </div>
                        <div class="shrink-0">
                            <flux:switch wire:model="pinOnPost" data-test="pin-on-post-switch" />
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Action row --}}
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <flux:button
                type="submit"
                variant="primary"
                icon="paper-airplane"
                :disabled="trim($title) === ''"
                data-test="post-button"
            >
                {{ $allowReplies ? 'Post conversation' : 'Post · replies off' }}
            </flux:button>

            <flux:button :href="route('groups.show', $group)" variant="ghost" wire:navigate>
                Cancel
            </flux:button>

            <div class="flex-1"></div>

            <span class="hidden text-xs text-zinc-500 sm:inline dark:text-zinc-400" data-test="posting-context">
                Posting in <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $group->name }}</span>
                as <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ auth()->user()->name }}</span>
            </span>
        </div>
    </form>
</section>
