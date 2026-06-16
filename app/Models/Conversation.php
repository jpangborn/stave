<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Comments\Models\Concerns\HasComments;
use Spatie\Comments\Models\Concerns\Interfaces\CanComment;

/**
 * @property ?Carbon $last_comment_at
 * @property ?Carbon $pinned_at
 * @property ?int $pinned_by_user_id
 * @property bool $allow_replies
 * @property ?string $title
 * @property int $group_id
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
        return $this->allow_replies;
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

    /** @return MorphMany<Comment, $this> */
    public function lastComment(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->with('commentator')
            ->latest()
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
        return $this->title ?? 'Direct message';
    }

    public function commentUrl(): string
    {
        if ($this->group->is_direct) {
            return $this->directUrl();
        }

        return route('groups.conversations.show', [
            'group' => $this->group_id,
            'conversation' => $this,
        ]);
    }

    public function directUrl(): string
    {
        return route('messages.show', ['conversation' => $this]);
    }

    public function isDirect(): bool
    {
        return $this->group->is_direct;
    }

    /**
     * Per-(conversation, user) read-state used for unread badges.
     *
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function lastReadAtFor(User $user): ?Carbon
    {
        /** @var ?User $participant */
        $participant = $this->participants()->where('users.id', $user->id)->first();

        $value = $participant?->getRelationValue('pivot')?->last_read_at;

        return $value !== null ? Carbon::parse($value) : null;
    }

    /**
     * Count of messages the user has not yet seen (excludes the user's own).
     */
    public function unreadCountFor(User $user): int
    {
        $lastReadAt = $this->lastReadAtFor($user);

        return $this->comments()
            ->whereNot(fn ($query) => $query
                ->where('commentator_id', $user->id)
                ->where('commentator_type', $user->getMorphClass())
            )
            ->when($lastReadAt, fn ($query) => $query->where('created_at', '>', $lastReadAt))
            ->count();
    }

    public function markReadFor(User $user): void
    {
        $this->participants()->syncWithoutDetaching([
            $user->id => ['last_read_at' => now()],
        ]);
    }

    /**
     * Members of the underlying direct group other than the viewer.
     *
     * @return Collection<int, User>
     */
    public function otherMembersFor(User $viewer): Collection
    {
        return $this->group->members
            ->reject(fn (User $member): bool => $member->id === $viewer->id)
            ->values();
    }

    /**
     * Viewer-relative title: the other person's full name for a 1:1, or the
     * other members' first names joined for an ad-hoc multi-person thread.
     */
    public function displayTitleFor(User $viewer): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        $others = $this->otherMembersFor($viewer);

        $first = $others->first();

        if ($others->count() <= 1) {
            return $first instanceof User ? $first->name : 'Direct message';
        }

        return self::joinNames(
            $others->map(fn (User $member): string => Str::of($member->name)->trim()->before(' ')->toString())->all()
        );
    }

    /**
     * Subline beneath the thread title.
     */
    public function sublineFor(User $viewer): string
    {
        $others = $this->otherMembersFor($viewer);

        if ($others->count() <= 1) {
            return 'Direct message';
        }

        $firstNames = $others
            ->map(fn (User $member): string => Str::of($member->name)->trim()->before(' ')->toString())
            ->implode(', ');

        return $firstNames.' · '.($others->count() + 1).' people';
    }

    /**
     * Join names as "A", "A & B", "A, B & C", "A, B, C & D".
     *
     * @param  array<int, string>  $names
     */
    private static function joinNames(array $names): string
    {
        $names = array_values(array_filter($names, fn (string $name): bool => $name !== ''));
        $count = count($names);

        if ($count === 0) {
            return 'Direct message';
        }

        if ($count === 1) {
            return $names[0];
        }

        $last = array_pop($names);

        return implode(', ', $names).' & '.$last;
    }
}
