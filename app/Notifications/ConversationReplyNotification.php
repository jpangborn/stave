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

class ConversationReplyNotification extends Notification implements ShouldQueue
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
            ->line("{$this->author->name} replied in \"{$this->conversation->title}\":")
            ->line($this->commentPreview($this->comment->text, 400))
            ->action('View Reply', $payload['url']);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
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
            'conversation_title' => $this->conversation->title,
            'group_id' => $this->conversation->group_id,
            'comment_id' => $this->comment->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
        ];
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return [
            'title' => "New reply in {$this->conversation->title}",
            'body' => "{$this->author->name}: ".$this->commentPreview($this->comment->text),
            'url' => $this->conversation->commentUrl().'#comment-'.$this->comment->id,
        ];
    }
}
