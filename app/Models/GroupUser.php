<?php

namespace App\Models;

use App\Enums\GroupRole;
use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property GroupRole $role
 * @property MembershipStatus $status
 */
#[Table(name: 'group_user')]
class GroupUser extends Pivot
{
    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'role' => GroupRole::class,
            'status' => MembershipStatus::class,
        ];
    }
}
