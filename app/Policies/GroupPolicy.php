<?php

namespace App\Policies;

use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can view the group.
     * Public groups are visible to all. Private groups only to active members/leaders.
     */
    public function view(User $user, Group $group): bool
    {
        if ($group->visibility === GroupVisibility::PUBLIC) {
            return true;
        }

        return $this->isActiveMember($user, $group);
    }

    /**
     * Determine whether the user can request to join the group.
     * Only public groups. User must not already be active or pending.
     */
    public function join(User $user, Group $group): bool
    {
        if ($group->visibility !== GroupVisibility::PUBLIC) {
            return false;
        }

        return ! $group->allUsers()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [MembershipStatus::REJECTED])
            ->exists();
    }

    /**
     * Determine whether the user can manage members (approve, reject, add, remove).
     */
    public function manageMembers(User $user, Group $group): bool
    {
        return $this->isLeader($user, $group);
    }

    /**
     * Determine whether the user can leave the group.
     * Active members can leave, but the sole leader cannot.
     */
    public function leave(User $user, Group $group): bool
    {
        if (! $this->isActiveMember($user, $group)) {
            return false;
        }

        if ($this->isLeader($user, $group) && $group->leaders()->count() === 1) {
            return false;
        }

        return true;
    }

    private function isActiveMember(User $user, Group $group): bool
    {
        return $group->members()->where('user_id', $user->id)->exists();
    }

    private function isLeader(User $user, Group $group): bool
    {
        return $group->leaders()->where('user_id', $user->id)->exists();
    }
}
