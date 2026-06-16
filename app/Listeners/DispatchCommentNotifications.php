<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\MutedCommentable;
use App\Models\Service;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\ConversationReplyNotification;
use App\Notifications\DirectMessageNotification;
use App\Notifications\ServiceDiscussionCommentNotification;
use App\Recipients\ResolveConversationReplyRecipients;
use App\Recipients\ResolveMentionRecipients;
use App\Recipients\ResolveServiceDiscussionRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Spatie\Comments\Events\CommentApprovedEvent;

class DispatchCommentNotifications implements ShouldQueue
{
    public function __construct(
        private ResolveConversationReplyRecipients $resolveConversationRecipients,
        private ResolveServiceDiscussionRecipients $resolveServiceRecipients,
        private ResolveMentionRecipients $resolveMentionRecipients,
    ) {}

    public function handle(CommentApprovedEvent $event): void
    {
        $comment = $event->comment;

        if (! $comment instanceof Comment) {
            return;
        }

        $author = $comment->commentator;

        if (! $author instanceof User) {
            return;
        }

        $commentable = $comment->commentable;

        if (! $commentable instanceof Conversation && ! $commentable instanceof Service) {
            return;
        }

        // Direct-message threads notify every other member (not just past
        // commentators), so a first message reaches a brand-new recipient.
        if ($commentable instanceof Conversation && $commentable->group->is_direct) {
            $this->dispatchDirectMessage($commentable, $comment, $author);

            return;
        }

        $mentioned = ($this->resolveMentionRecipients)($comment);

        $primary = $commentable instanceof Conversation
            ? ($this->resolveConversationRecipients)($commentable, $author)
            : ($this->resolveServiceRecipients)($commentable, $author);

        $mentionedIds = $mentioned->pluck('id');

        $primary = $primary->reject(
            fn (User $user): bool => $mentionedIds->contains($user->id),
        )->values();

        $primary = MutedCommentable::filterMuted($primary, $commentable);

        if ($mentioned->isNotEmpty()) {
            Notification::send($mentioned, new CommentMentionNotification($comment, $author));
        }

        if ($primary->isNotEmpty()) {
            $primaryNotification = $commentable instanceof Conversation
                ? new ConversationReplyNotification($commentable, $comment, $author)
                : new ServiceDiscussionCommentNotification($commentable, $comment, $author);

            Notification::send($primary, $primaryNotification);
        }
    }

    private function dispatchDirectMessage(Conversation $conversation, Comment $comment, User $author): void
    {
        /** @var Collection<int, User> $recipients */
        $recipients = $conversation->group->members()
            ->where('users.id', '!=', $author->id)
            ->get();

        $recipients = MutedCommentable::filterMuted($recipients, $conversation);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new DirectMessageNotification($conversation, $comment, $author));
        }
    }
}
