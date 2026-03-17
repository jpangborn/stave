<?php

namespace App\Models;

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property GroupVisibility $visibility
 * @property GroupMessaging $messaging
 */
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'image', 'visibility', 'messaging'];

    protected static function booted(): void
    {
        static::deleting(function (Group $group): void {
            if ($group->image) {
                Storage::disk('digital-ocean')->delete($group->image);
            }
        });
    }

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'visibility' => GroupVisibility::class,
            'messaging' => GroupMessaging::class,
        ];
    }

    /** @return BelongsToMany<User, $this, GroupUser> */
    public function members(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('status', MembershipStatus::ACTIVE);
    }

    /** @return BelongsToMany<User, $this, GroupUser> */
    public function leaders(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('role', GroupRole::LEADER)
            ->wherePivot('status', MembershipStatus::ACTIVE);
    }

    /** @return BelongsToMany<User, $this, GroupUser> */
    public function pendingRequests(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('status', MembershipStatus::PENDING);
    }

    /** @return BelongsToMany<User, $this, GroupUser> */
    public function allUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(GroupUser::class)
            ->withPivot('role', 'status')
            ->withTimestamps();
    }
}
