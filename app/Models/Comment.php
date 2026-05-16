<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Comments\Models\Comment as SpatieComment;

/**
 * @property ?Carbon $pinned_at
 * @property ?int $pinned_by_user_id
 * @property bool $is_prayer
 */
class Comment extends SpatieComment
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'is_prayer' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by_user_id');
    }

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    public function pin(User $pinnedBy): self
    {
        $this->forceFill([
            'pinned_at' => now(),
            'pinned_by_user_id' => $pinnedBy->id,
        ])->save();

        return $this;
    }

    public function unpin(): self
    {
        $this->forceFill([
            'pinned_at' => null,
            'pinned_by_user_id' => null,
        ])->save();

        return $this;
    }

    public function togglePrayer(): self
    {
        $this->forceFill(['is_prayer' => ! $this->is_prayer])->save();

        return $this;
    }
}
