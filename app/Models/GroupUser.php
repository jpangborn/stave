<?php

namespace App\Models;

use App\Enums\GroupMembershipStatus;
use App\Enums\GroupRole;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property GroupRole $role
 * @property GroupMembershipStatus $status
 */
#[Table(name: 'group_user')]
class GroupUser extends Pivot
{
    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'role' => GroupRole::class,
            'status' => GroupMembershipStatus::class,
        ];
    }
}
