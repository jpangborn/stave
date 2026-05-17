<?php

namespace App\Policies;

use App\Enums\GroupMessaging;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user, Group $group): bool
    {
        return $group->hasActiveMember($user);
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->group->hasActiveMember($user);
    }

    public function create(User $user, Group $group): bool
    {
        return $this->canPost($user, $group);
    }

    public function comment(User $user, Conversation $conversation): bool
    {
        return $this->canPost($user, $conversation->group);
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->isAuthorOrLeader($user, $conversation);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $conversation->group->hasLeader($user);
    }

    private function isAuthorOrLeader(User $user, Conversation $conversation): bool
    {
        return $this->isAuthor($user, $conversation)
            || $conversation->group->hasLeader($user);
    }

    private function canPost(User $user, Group $group): bool
    {
        return match ($group->messaging) {
            GroupMessaging::OFF => false,
            GroupMessaging::ALL_MEMBERS => $group->hasActiveMember($user),
            GroupMessaging::ONLY_LEADERS => $group->hasLeader($user),
        };
    }

    private function isAuthor(User $user, Conversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
}
