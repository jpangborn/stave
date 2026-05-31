<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationEventType;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\User;
use App\Notifications\Concerns\HasCommentPreview;
use App\Notifications\Concerns\RespectsNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use RuntimeException;

class CommentMentionNotification extends Notification implements ShouldQueue
{
    use HasCommentPreview, Queueable, RespectsNotificationPreferences;

    public function __construct(
        public Comment $comment,
        public User $author,
    ) {}

    public function eventType(): NotificationEventType
    {
        return NotificationEventType::COMMENT_MENTION;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->payload();

        return (new MailMessage())
            ->subject('[Mention] '.$payload['title'])
            ->line("{$this->author->name} mentioned you in {$this->contextLabel()}:")
            ->line($this->commentPreview($this->comment->text, 400))
            ->action('View Comment', $payload['url']);
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
            ->tag('mention-'.$this->comment->id)
            ->requireInteraction()
            ->data(['url' => $payload['url']]);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            ...$this->payload(),
            'type' => $this->eventType()->value,
            'comment_id' => $this->comment->id,
            'commentable_type' => $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
        ];
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return [
            'title' => "{$this->author->name} mentioned you",
            'body' => $this->commentPreview($this->comment->text),
            'url' => $this->urlForCommentable(),
        ];
    }

    private function urlForCommentable(): string
    {
        $commentable = $this->comment->commentable;

        return match (true) {
            $commentable instanceof Conversation => $commentable->commentUrl().'#comment-'.$this->comment->id,
            $commentable instanceof Service => route('services.show', [$commentable, 'tab' => 'discussion']),
            default => throw new RuntimeException('Unsupported commentable type for mention notification: '.$this->comment->commentable_type),
        };
    }

    private function contextLabel(): string
    {
        $commentable = $this->comment->commentable;

        return match (true) {
            $commentable instanceof Conversation => "the conversation \"{$commentable->title}\"",
            $commentable instanceof Service => "the discussion for \"{$commentable->title}\"",
            default => 'a comment',
        };
    }
}
