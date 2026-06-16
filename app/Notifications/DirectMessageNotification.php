<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationEventType;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\Concerns\HasCommentPreview;
use App\Notifications\Concerns\RespectsNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class DirectMessageNotification extends Notification implements ShouldQueue
{
    use HasCommentPreview, Queueable, RespectsNotificationPreferences;

    public function __construct(
        public Conversation $conversation,
        public Comment $comment,
        public User $author,
    ) {}

    public function eventType(): NotificationEventType
    {
        return NotificationEventType::CONVERSATION_REPLY;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->payload();

        return (new MailMessage())
            ->subject($payload['title'])
            ->line("{$this->author->name} sent you a message:")
            ->line($this->commentPreview($this->comment->text, 400))
            ->action('View Message', $payload['url']);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            ...$this->payload(),
            'conversation_id' => $this->conversation->id,
            'is_direct' => true,
        ]);
    }

    public function toWebPush(object $notifiable, ?Notification $notification): WebPushMessage
    {
        $payload = $this->payload();

        return (new WebPushMessage())
            ->title($payload['title'])
            ->body($payload['body'])
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->data(['url' => $payload['url']]);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            ...$this->payload(),
            'type' => $this->eventType()->value,
            'conversation_id' => $this->conversation->id,
            'group_id' => $this->conversation->group_id,
            'comment_id' => $this->comment->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'is_direct' => true,
        ];
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return [
            'title' => "New message from {$this->author->name}",
            'body' => "{$this->author->name}: ".$this->commentPreview($this->comment->text),
            'url' => $this->conversation->directUrl(),
        ];
    }
}
