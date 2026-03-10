<?php

namespace App\Models;

use App\Enums\GroupRole;
use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property GroupRole $role
 * @property MembershipStatus $status
 */
class GroupUser extends Pivot
{
    protected $table = 'group_user';

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'role' => GroupRole::class,
            'status' => MembershipStatus::class,
        ];
    }
}
