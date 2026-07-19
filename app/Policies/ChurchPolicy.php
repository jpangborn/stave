<?php

namespace App\Policies;

use App\Enums\AccessRole;
use App\Models\Church;
use App\Models\User;

class ChurchPolicy
{
    public function update(User $user, Church $church): bool
    {
        return $user->hasAccessRole(AccessRole::ADMIN, $church);
    }

    public function manageMembers(User $user, Church $church): bool
    {
        return $user->hasAccessRole(AccessRole::ADMIN, $church);
    }
}
