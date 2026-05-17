<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\Comments\Models\Concerns\HasComments;
use Spatie\Comments\Models\Concerns\Interfaces\CanComment;

/**
 * @property ?Carbon $last_comment_at
 * @property ?Carbon $pinned_at
 * @property ?int $pinned_by_user_id
 * @property bool $allow_replies
 */
#[Fillable(['group_id', 'user_id', 'title', 'allow_replies'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasComments, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_comment_at' => 'datetime',
            'pinned_at' => 'datetime',
            'allow_replies' => 'boolean',
        ];
    }

    public function allowsReplies(): bool
    {
        return (bool) $this->allow_replies;
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by_user_id');
    }

    /** @return MorphMany<Comment, $this> */
    public function pinnedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->whereNotNull('pinned_at')
            ->orderByDesc('pinned_at');
    }

    /** @return MorphMany<Comment, $this> */
    public function firstComment(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->with('commentator')
            ->oldest()
            ->limit(1);
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

    /** @return HasMany<ConversationFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ConversationFile::class);
    }

    /** @return HasMany<ConversationFile, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(ConversationFile::class)->where('is_inline_image', false);
    }

    public function postComment(string $text, ?CanComment $commentator = null, bool $isPrayer = false): Comment
    {
        /** @var Comment $comment */
        $comment = $this->comment($text, $commentator);

        if ($isPrayer) {
            $comment->forceFill(['is_prayer' => true])->save();
        }

        $this->forceFill(['last_comment_at' => $comment->created_at])->save();

        return $comment;
    }

    public function commentableName(): string
    {
        return $this->title;
    }

    public function commentUrl(): string
    {
        return route('groups.conversations.show', [
            'group' => $this->group_id,
            'conversation' => $this,
        ]);
    }
}
