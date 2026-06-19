<?php

namespace App\Policies;

use App\Enums\AccessRole;
use App\Models\PastoralNote;
use App\Models\User;

class PastoralNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessPastoralCare();
    }

    public function view(User $user, PastoralNote $pastoralNote): bool
    {
        return $user->canAccessPastoralCare();
    }

    public function create(User $user): bool
    {
        return $user->canAccessPastoralCare();
    }

    public function update(User $user, PastoralNote $pastoralNote): bool
    {
        return $this->isAuthorOrAdmin($user, $pastoralNote);
    }

    public function delete(User $user, PastoralNote $pastoralNote): bool
    {
        return $this->isAuthorOrAdmin($user, $pastoralNote);
    }

    private function isAuthorOrAdmin(User $user, PastoralNote $pastoralNote): bool
    {
        return $pastoralNote->author_id === $user->id
            || $user->hasAccessRole(AccessRole::PASTORAL_CARE_ADMIN)
            || $user->hasAccessRole(AccessRole::ADMIN);
    }
}
