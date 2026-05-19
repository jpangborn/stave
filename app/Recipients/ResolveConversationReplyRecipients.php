<?php

declare(strict_types=1);

namespace App\Recipients;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;

class ResolveConversationReplyRecipients
{
    /** @return Collection<int, User> */
    public function __invoke(Conversation $conversation, User $author): Collection
    {
        return $conversation->participatingCommentators()
            ->filter(fn ($user): bool => $user instanceof User)
            ->reject(fn (User $user): bool => $user->id === $author->id)
            ->unique('id')
            ->values();
    }
}
