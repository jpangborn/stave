<?php

namespace App\Notifications;

use Illuminate\Support\Str;
use Spatie\Comments\Notifications\ApprovedCommentNotification;

class ServiceCommentNotification extends ApprovedCommentNotification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'commentable_type' => $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
            'commenter_name' => $this->comment->commentator?->name,
            'comment_preview' => Str::limit(
                strip_tags($this->comment->text),
                100
            ),
        ];
    }
}
