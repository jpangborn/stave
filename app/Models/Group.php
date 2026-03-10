<?php

namespace App\Models;

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property GroupVisibility $visibility
 * @property GroupMessaging $messaging
 */
class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'image', 'visibility', 'messaging'];

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'visibility' => GroupVisibility::class,
            'messaging' => GroupMessaging::class,
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('status', MembershipStatus::ACTIVE);
    }

    /** @return BelongsToMany<User, $this> */
    public function leaders(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('role', GroupRole::LEADER)
            ->wherePivot('status', MembershipStatus::ACTIVE);
    }

    /** @return BelongsToMany<User, $this> */
    public function pendingRequests(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('status', MembershipStatus::PENDING);
    }

    /** @return BelongsToMany<User, $this> */
    public function allUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'status')
            ->withTimestamps();
    }
}
