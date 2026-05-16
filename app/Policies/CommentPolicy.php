<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\User;

class CommentPolicy
{
    public function pin(User $user, Comment $comment): bool
    {
        $conversation = $this->conversationFor($comment);

        if (! $conversation instanceof Conversation) {
            return false;
        }

        return $this->isAuthor($user, $comment)
            || $conversation->group->hasLeader($user);
    }

    public function unpin(User $user, Comment $comment): bool
    {
        return $this->pin($user, $comment);
    }

    public function markPrayer(User $user, Comment $comment): bool
    {
        $conversation = $this->conversationFor($comment);

        if (! $conversation instanceof Conversation) {
            return false;
        }

        return $conversation->group->hasActiveMember($user);
    }

    private function conversationFor(Comment $comment): ?Conversation
    {
        $commentable = $comment->commentable;

        return $commentable instanceof Conversation ? $commentable : null;
    }

    private function isAuthor(User $user, Comment $comment): bool
    {
        return $comment->commentator_type === $user->getMorphClass()
            && $comment->commentator_id === $user->id;
    }
}
