<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Service;
use App\Models\User;
use App\Notifications\Concerns\HasCommentPreview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class ServiceDiscussionCommentNotification extends Notification implements ShouldQueue
{
    use HasCommentPreview, Queueable;

    public function __construct(
        public Service $service,
        public Comment $comment,
        public User $author,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast', 'webpush', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->payload();

        return (new MailMessage())
            ->subject($payload['title'])
            ->line("{$this->author->name} posted in the discussion for \"{$this->service->title}\":")
            ->line($this->commentPreview($this->comment->text, 400))
            ->action('View Discussion', $payload['url']);
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
            'type' => 'service.discussion.comment',
            'service_id' => $this->service->id,
            'service_title' => $this->service->title,
            'comment_id' => $this->comment->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
        ];
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return [
            'title' => "Service discussion: {$this->service->title}",
            'body' => "{$this->author->name}: ".$this->commentPreview($this->comment->text),
            'url' => route('services.show', $this->service).'#discussion',
        ];
    }
}
