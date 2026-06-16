<?php

namespace App\Models;

use App\Enums\GroupMembershipStatus;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Casts\CleanHtmlInput;

/**
 * @property GroupVisibility $visibility
 * @property GroupMessaging $messaging
 * @property bool $is_direct
 * @property ?string $direct_key
 */
#[Fillable(['name', 'description', 'image', 'visibility', 'messaging', 'is_direct', 'direct_key'])]
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
            'is_direct' => 'boolean',
        ];
    }

    /**
     * Direct (1:1 or ad-hoc multi-person) message groups.
     *
     * @param  Builder<Group>  $query
     */
    public function scopeDirect(Builder $query): void
    {
        $query->where('is_direct', true);
    }

    /**
     * Named groups (everything that is not a direct-message group).
     *
     * @param  Builder<Group>  $query
     */
    public function scopeNotDirect(Builder $query): void
    {
        $query->where('is_direct', false);
    }

    /** @return BelongsToMany<User, $this, GroupUser> */
    public function members(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('status', GroupMembershipStatus::ACTIVE);
    }

    /** @return BelongsToMany<User, $this, GroupUser> */
    public function leaders(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('role', GroupRole::LEADER)
            ->wherePivot('status', GroupMembershipStatus::ACTIVE);
    }

    /** @return BelongsToMany<User, $this, GroupUser> */
    public function pendingRequests(): BelongsToMany
    {
        return $this->allUsers()
            ->wherePivot('status', GroupMembershipStatus::PENDING);
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

    /** @return HasOne<Conversation, $this> */
    public function latestConversation(): HasOne
    {
        return $this->hasOne(Conversation::class)
            ->whereNotNull('last_comment_at')
            ->latestOfMany('last_comment_at');
    }

    /**
     * The single conversation that a direct-message group owns.
     *
     * @return HasOne<Conversation, $this>
     */
    public function directConversation(): HasOne
    {
        return $this->hasOne(Conversation::class)->oldestOfMany();
    }

    /**
     * Normalized key for a 1:1 direct group (sorted member id pair).
     *
     * @param  array<int, int>  $userIds
     */
    public static function directKeyFor(array $userIds): string
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        sort($userIds);

        return implode('-', $userIds);
    }

    /**
     * Resolve the direct-message group for the given participants, creating it
     * (with its single conversation and read-state rows) if needed. 1:1 pairs are
     * deduped via `direct_key`; multi-person ad-hoc groups are always created fresh.
     *
     * @param  array<int, int>  $otherUserIds
     */
    public static function findOrCreateDirect(User $creator, array $otherUserIds): self
    {
        $otherUserIds = array_values(array_filter(
            array_unique(array_map('intval', $otherUserIds)),
            fn (int $id): bool => $id !== $creator->id,
        ));

        $allIds = [$creator->id, ...$otherUserIds];
        sort($allIds);

        $isOneToOne = count($allIds) === 2;
        $directKey = $isOneToOne ? self::directKeyFor($allIds) : null;

        return DB::transaction(function () use ($creator, $allIds, $directKey): self {
            if ($directKey !== null) {
                $existing = self::query()->direct()->where('direct_key', $directKey)->first();

                if ($existing instanceof self) {
                    return $existing;
                }
            }

            try {
                $group = self::create([
                    'name' => null,
                    'visibility' => GroupVisibility::PRIVATE,
                    'messaging' => GroupMessaging::ALL_MEMBERS,
                    'is_direct' => true,
                    'direct_key' => $directKey,
                ]);
            } catch (QueryException $exception) {
                if ($directKey !== null && in_array($exception->getCode(), ['23000', '23505'], true)) {
                    $existing = self::query()->direct()->where('direct_key', $directKey)->first();

                    if ($existing instanceof self) {
                        return $existing;
                    }
                }

                throw $exception;
            }

            $group->allUsers()->attach(collect($allIds)->mapWithKeys(fn (int $id): array => [
                $id => [
                    'role' => $id === $creator->id ? GroupRole::LEADER : GroupRole::MEMBER,
                    'status' => GroupMembershipStatus::ACTIVE,
                ],
            ])->all());

            $conversation = $group->conversations()->create([
                'user_id' => $creator->id,
                'title' => null,
                'allow_replies' => true,
            ]);

            $conversation->participants()->attach($allIds);

            return $group;
        });
    }

    public function hasActiveMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }

    public function hasLeader(User $user): bool
    {
        return $this->leaders()->whereKey($user->id)->exists();
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image
            ? Storage::disk('digital-ocean')->url($this->image)
            : null);
    }

    protected function firstLetter(): Attribute
    {
        return Attribute::get(function (): string {
            $first = Str::upper(Str::substr(trim((string) ($this->name ?? '')), 0, 1));

            return $first !== '' ? $first : '?';
        });
    }
}
