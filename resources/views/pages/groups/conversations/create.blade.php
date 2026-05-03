<?php

use App\Models\Conversation;
use App\Models\Group;
use App\Notifications\NewConversationNotification;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

new class extends Component {
    public Group $group;

    public string $title = '';
    public string $body = '';

    public function mount(Group $group): void
    {
        $this->authorize('create', [Conversation::class, $group]);

        $this->group = $group;
    }

    public function save(): void
    {
        $this->authorize('create', [Conversation::class, $this->group]);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        if (trim(strip_tags($validated['body'])) === '') {
            $this->addError('body', 'Please write an opening message.');

            return;
        }

        $conversation = DB::transaction(function () use ($validated): Conversation {
            $conversation = $this->group->conversations()->create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
            ]);

            $conversation->postComment($validated['body'], Auth::user());

            return $conversation;
        });

        $this->notifyMembers($conversation);

        Flux::toast(variant: 'success', text: 'Conversation started.');

        $this->redirect(route('groups.conversations.show', [
            'group' => $this->group,
            'conversation' => $conversation,
        ]), navigate: true);
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

<section class="w-full">
    <flux:heading size="xl" level="1">Start a Conversation</flux:heading>
    <flux:subheading size="lg" class="mb-6">Post a new conversation in {{ $group->name }}.</flux:subheading>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:field>
            <flux:label badge="Required">Title</flux:label>
            <flux:input type="text" name="title" wire:model="title" />
            <flux:error name="title" />
        </flux:field>

        <flux:editor
            label="Message"
            wire:model="body"
            toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
            class="**:data-[slot=content]:min-h-[200px]"
        />
        <flux:error name="body" />

        <div class="flex items-center gap-2">
            <flux:button type="submit" variant="primary" icon="paper-airplane">Post</flux:button>
            <flux:button :href="route('groups.show', $group)" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</section>
