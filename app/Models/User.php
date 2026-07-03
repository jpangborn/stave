<?php

namespace App\Models;

use App\Enums\AccessRole;
use App\Enums\DigestFrequency;
use App\Enums\GroupMembershipStatus;
use App\Models\Traits\HasGravatar;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Comments\Models\Concerns\InteractsWithComments;
use Spatie\Comments\Models\Concerns\Interfaces\CanComment;

/** @property DigestFrequency $digest_frequency */
#[Fillable(['name', 'email', 'password', 'person_id', 'quiet_hours_start', 'quiet_hours_end', 'timezone', 'digest_frequency'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanComment
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasGravatar, HasPushSubscriptions, InteractsWithComments, Notifiable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'digest_frequency' => 'daily',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'digest_frequency' => DigestFrequency::class,
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /** @return Collection<int, AccessRole> */
    public function accessRoles(): Collection
    {
        return DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->pluck('role')
            ->map(fn (string $value) => AccessRole::tryFrom($value))
            ->filter();
    }

    public function hasAccessRole(AccessRole $role): bool
    {
        return DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->where('role', $role->value)
            ->exists();
    }

    /**
     * Whether the user may view pastoral-care surfaces (the Pastoral Care page,
     * pastoral notes, and the prayer schedule).
     */
    public function canAccessPastoralCare(): bool
    {
        return DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->whereIn('role', [
                AccessRole::PASTORAL_CARE_USER->value,
                AccessRole::PASTORAL_CARE_ADMIN->value,
                AccessRole::ADMIN->value,
            ])
            ->exists();
    }

    /**
     * Whether the user may view liturgy surfaces (service planning,
     * assignments, the song & reading library).
     */
    public function canAccessLiturgy(): bool
    {
        return DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->whereIn('role', [
                AccessRole::LITURGY_USER->value,
                AccessRole::LITURGY_ADMIN->value,
                AccessRole::ADMIN->value,
            ])
            ->exists();
    }

    /**
     * Whether the user may manage liturgy (readiness overview, rotation
     * planning, library and template administration).
     */
    public function canManageLiturgy(): bool
    {
        return DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->whereIn('role', [
                AccessRole::LITURGY_ADMIN->value,
                AccessRole::ADMIN->value,
            ])
            ->exists();
    }

    public function grantAccessRole(AccessRole $role): void
    {
        DB::table('user_access_roles')->insertOrIgnore([
            'user_id' => $this->id,
            'role' => $role->value,
            'created_at' => now(),
        ]);
    }

    public function revokeAccessRole(AccessRole $role): void
    {
        DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->where('role', $role->value)
            ->delete();
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsToMany<Group, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('role', 'status')
            ->withTimestamps()
            ->wherePivot('status', GroupMembershipStatus::ACTIVE);
    }

    /**
     * Conversations belonging to the user's direct-message groups.
     *
     * @return Builder<Conversation>
     */
    public function directConversations(): Builder
    {
        return Conversation::query()
            ->whereHas('group', fn (Builder $query): Builder => $query
                ->where('is_direct', true)
                ->whereHas('allUsers', fn (Builder $inner): Builder => $inner
                    ->where('users.id', $this->id)
                    ->where('status', GroupMembershipStatus::ACTIVE->value)
                )
            );
    }

    /**
     * Total unread direct messages across all of the user's conversations.
     */
    public function unreadDirectCount(): int
    {
        return Comment::query()
            ->where('comments.commentable_type', (new Conversation())->getMorphClass())
            ->whereIn('comments.commentable_id', $this->directConversations()->select('conversations.id'))
            ->whereNot(fn ($q) => $q
                ->where('comments.commentator_id', $this->id)
                ->where('comments.commentator_type', $this->getMorphClass())
            )
            ->leftJoin('conversation_user', fn ($join) => $join
                ->on('conversation_user.conversation_id', '=', 'comments.commentable_id')
                ->where('conversation_user.user_id', $this->id)
            )
            ->where(fn ($q) => $q
                ->whereNull('conversation_user.last_read_at')
                ->orWhereColumn('comments.created_at', '>', 'conversation_user.last_read_at')
            )
            ->count();
    }

    /**
     * Liturgy elements assigned to this user in upcoming (today or later)
     * services, ordered by the parent service's date ascending.
     *
     * @return Collection<int, LiturgyElement>
     */
    public function upcomingAssignments(): Collection
    {
        return LiturgyElement::query()
            ->where('assignee_id', $this->id)
            ->whereHasMorph('liturgy', Service::class, fn (Builder $query): Builder => $query->upcoming())
            ->with(['liturgy', 'content'])
            ->get()
            ->sortBy(fn (LiturgyElement $element): string => $element->liturgy->date->toDateString())
            ->values();
    }

    /**
     * Per-group unread message counts for the user's active, non-direct
     * groups, keyed by group id. Groups with zero unread messages are
     * omitted from the collection.
     *
     * @return Collection<int, int>
     */
    public function unreadGroupCounts(): Collection
    {
        return Comment::query()
            ->where('comments.commentable_type', (new Conversation())->getMorphClass())
            ->whereIn('comments.commentable_id', Conversation::query()
                ->whereHas('group', fn (Builder $query): Builder => $query
                    ->where('is_direct', false)
                    ->whereHas('allUsers', fn (Builder $inner): Builder => $inner
                        ->where('users.id', $this->id)
                        ->where('status', GroupMembershipStatus::ACTIVE->value)
                    )
                )
                ->select('conversations.id')
            )
            ->whereNot(fn ($q) => $q
                ->where('comments.commentator_id', $this->id)
                ->where('comments.commentator_type', $this->getMorphClass())
            )
            ->leftJoin('conversation_user', fn ($join) => $join
                ->on('conversation_user.conversation_id', '=', 'comments.commentable_id')
                ->where('conversation_user.user_id', $this->id)
            )
            ->where(fn ($q) => $q
                ->whereNull('conversation_user.last_read_at')
                ->orWhereColumn('comments.created_at', '>', 'conversation_user.last_read_at')
            )
            ->join('conversations', 'conversations.id', '=', 'comments.commentable_id')
            ->join('groups', 'groups.id', '=', 'conversations.group_id')
            ->groupBy('groups.id')
            ->selectRaw('groups.id as group_id, count(*) as unread_count')
            ->pluck('unread_count', 'group_id');
    }

    /**
     * Per-conversation unread message counts for the user's direct
     * conversations, keyed by conversation id. Conversations with zero
     * unread messages are omitted from the collection.
     *
     * @return Collection<int, int>
     */
    public function unreadDirectCounts(): Collection
    {
        return Comment::query()
            ->where('comments.commentable_type', (new Conversation())->getMorphClass())
            ->whereIn('comments.commentable_id', $this->directConversations()->select('conversations.id'))
            ->whereNot(fn ($q) => $q
                ->where('comments.commentator_id', $this->id)
                ->where('comments.commentator_type', $this->getMorphClass())
            )
            ->leftJoin('conversation_user', fn ($join) => $join
                ->on('conversation_user.conversation_id', '=', 'comments.commentable_id')
                ->where('conversation_user.user_id', $this->id)
            )
            ->where(fn ($q) => $q
                ->whereNull('conversation_user.last_read_at')
                ->orWhereColumn('comments.created_at', '>', 'conversation_user.last_read_at')
            )
            ->groupBy('comments.commentable_id')
            ->selectRaw('comments.commentable_id as conversation_id, count(*) as unread_count')
            ->pluck('unread_count', 'conversation_id');
    }

    /** @return HasMany<NotificationPreference, $this> */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /** @return HasMany<EmailDigestItem, $this> */
    public function emailDigestItems(): HasMany
    {
        return $this->hasMany(EmailDigestItem::class);
    }

    /** @return HasMany<EmailDigestItem, $this> */
    public function pendingDigestItems(): HasMany
    {
        return $this->emailDigestItems()->whereNull('sent_at');
    }

    /** @return HasMany<MutedCommentable, $this> */
    public function mutedCommentables(): HasMany
    {
        return $this->hasMany(MutedCommentable::class);
    }

    public function hasMuted(Model $commentable): bool
    {
        return $this->mutedCommentables()
            ->where('commentable_type', $commentable->getMorphClass())
            ->where('commentable_id', $commentable->getKey())
            ->exists();
    }
}
