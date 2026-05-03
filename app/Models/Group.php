<?php

namespace App\Models;

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Casts\CleanHtmlInput;

/**
 * @property GroupVisibility $visibility
 * @property GroupMessaging $messaging
 */
#[Fillable(['name', 'description', 'image', 'visibility', 'messaging'])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Group $group): void {
            if ($group->image) {
                Storage::disk('digital-ocean')->delete($group->image);
            }
        });
    }

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'visibility' => GroupVisibility::class,
            'messaging' => GroupMessaging::class,
            'description' => CleanHtmlInput::class.':rich_text',
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

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function hasActiveMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }

    public function hasLeader(User $user): bool
    {
        return $this->leaders()->whereKey($user->id)->exists();
    }
}
