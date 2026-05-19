<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * @property int $user_id
 * @property string $commentable_type
 * @property int $commentable_id
 */
#[Fillable(['user_id', 'commentable_type', 'commentable_id'])]
class MutedCommentable extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Reject users who have muted the given commentable.
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    public static function filterMuted(Collection $users, Model $commentable): Collection
    {
        if ($users->isEmpty()) {
            return $users;
        }

        $mutedUserIds = static::query()
            ->where('commentable_type', $commentable::class)
            ->where('commentable_id', $commentable->getKey())
            ->whereIn('user_id', $users->pluck('id')->all())
            ->pluck('user_id')
            ->all();

        if ($mutedUserIds === []) {
            return $users;
        }

        return $users
            ->reject(fn (User $user): bool => in_array($user->id, $mutedUserIds, true))
            ->values();
    }
}
