<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Comments\Models\Comment as SpatieComment;

/**
 * @property ?Carbon $pinned_at
 * @property ?int $pinned_by_user_id
 * @property bool $is_prayer
 * @property ?Carbon $edited_at
 */
class Comment extends SpatieComment
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'is_prayer' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by_user_id');
    }

    /** @return HasMany<ConversationFile, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(ConversationFile::class)->where('is_inline_image', false);
    }

    /** @return HasMany<ConversationFile, $this> */
    public function inlineImages(): HasMany
    {
        return $this->hasMany(ConversationFile::class)->where('is_inline_image', true);
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

    public function markAsEdited(): self
    {
        $this->forceFill(['edited_at' => now()])->save();

        return $this;
    }
}
