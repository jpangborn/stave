<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\Comments\Models\Concerns\HasComments;
use Spatie\Comments\Models\Concerns\Interfaces\CanComment;

/**
 * @property ?Carbon $last_comment_at
 */
#[Fillable(['group_id', 'user_id', 'title'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasComments, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_comment_at' => 'datetime',
        ];
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

    /** @return MorphMany<Comment, $this> */
    public function pinnedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->whereNotNull('pinned_at')
            ->orderByDesc('pinned_at');
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
