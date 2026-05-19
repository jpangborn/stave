<?php

declare(strict_types=1);

namespace App\Recipients;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Collection;

class ResolveMentionRecipients
{
    /** @return Collection<int, User> */
    public function __invoke(Comment $comment): Collection
    {
        $authorId = $comment->commentator_id;

        return $comment->getMentionees()
            ->filter(fn ($user): bool => $user instanceof User)
            ->reject(fn (User $user): bool => $user->id === $authorId)
            ->unique('id')
            ->values();
    }
}
