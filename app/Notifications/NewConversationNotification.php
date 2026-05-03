<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewConversationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public User $author,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $group = $this->conversation->group;

        return (new MailMessage())
            ->subject("New conversation in {$group->name}")
            ->line("{$this->author->name} started a new conversation: {$this->conversation->title}")
            ->action('View Conversation', route('groups.conversations.show', [
                'group' => $group,
                'conversation' => $this->conversation,
            ]));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'conversation_title' => $this->conversation->title,
            'group_id' => $this->conversation->group_id,
            'group_name' => $this->conversation->group->name,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
        ];
    }
}
