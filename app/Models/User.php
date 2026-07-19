<?php

namespace App\Models;

use App\Enums\AccessRole;
use App\Enums\DigestFrequency;
use App\Enums\GroupMembershipStatus;
use App\Models\Traits\HasGravatar;
use App\Support\CurrentChurch;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
use LogicException;
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

    /**
     * Role sets already loaded this request, keyed by church id (0 = no
     * church), so repeated capability checks cost a single query.
     *
     * @var array<int, Collection<int, AccessRole>>
     */
    private array $resolvedAccessRoles = [];

    /**
     * The user's access roles in the given church (defaults to the current
     * church). Roles are church-scoped: a user can be an administrator in
     * one church and a plain member in another.
     *
     * @return Collection<int, AccessRole>
     */
    public function accessRoles(?Church $church = null): Collection
    {
        $churchId = $church->id ?? app(CurrentChurch::class)->id() ?? $this->current_church_id;

        return $this->resolvedAccessRoles[$churchId ?? 0] ??= DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->where('church_id', $churchId)
            ->pluck('role')
            ->map(fn (string $value) => AccessRole::tryFrom($value))
            ->filter()
            ->values();
    }

    public function hasAccessRole(AccessRole $role, ?Church $church = null): bool
    {
        return $this->accessRoles($church)->containsStrict($role);
    }

    /**
     * @param  array<int, AccessRole>  $roles
     */
    private function hasAnyAccessRole(array $roles): bool
    {
        return $this->accessRoles()
            ->contains(fn (AccessRole $role): bool => in_array($role, $roles, true));
    }

    /**
     * Whether the user may view pastoral-care surfaces (the Pastoral Care page,
     * pastoral notes, and the prayer schedule).
     */
    public function canAccessPastoralCare(): bool
    {
        return $this->hasAnyAccessRole([
            AccessRole::PASTORAL_CARE_USER,
            AccessRole::PASTORAL_CARE_ADMIN,
            AccessRole::ADMIN,
        ]);
    }

    /**
     * Whether the user may view liturgy surfaces (service planning,
     * assignments, the song & reading library).
     */
    public function canAccessLiturgy(): bool
    {
        return $this->hasAnyAccessRole([
            AccessRole::LITURGY_USER,
            AccessRole::LITURGY_ADMIN,
            AccessRole::ADMIN,
        ]);
    }

    /**
     * Whether the user may manage liturgy (readiness overview, rotation
     * planning, library and template administration).
     */
    public function canManageLiturgy(): bool
    {
        return $this->hasAnyAccessRole([
            AccessRole::LITURGY_ADMIN,
            AccessRole::ADMIN,
        ]);
    }

    public function grantAccessRole(AccessRole $role, ?Church $church = null): void
    {
        $churchId = $church->id ?? app(CurrentChurch::class)->id() ?? $this->current_church_id;

        DB::table('user_access_roles')->insertOrIgnore([
            'user_id' => $this->id,
            'church_id' => $churchId,
            'role' => $role->value,
            'created_at' => now(),
        ]);

        unset($this->resolvedAccessRoles[$churchId ?? 0]);
    }

    public function revokeAccessRole(AccessRole $role, ?Church $church = null): void
    {
        $churchId = $church->id ?? app(CurrentChurch::class)->id() ?? $this->current_church_id;

        DB::table('user_access_roles')
            ->where('user_id', $this->id)
            ->where('church_id', $churchId)
            ->where('role', $role->value)
            ->delete();

        unset($this->resolvedAccessRoles[$churchId ?? 0]);
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * The Person representing this user in the given church (defaults to the
     * current church). Multi-church users have a distinct Person per church,
     * tracked on the membership pivot; users.person_id is the fallback.
     */
    public function personFor(?Church $church = null): ?Person
    {
        $churchId = $church->id ?? app(CurrentChurch::class)->id() ?? $this->current_church_id;

        $pivotPersonId = DB::table('church_user')
            ->where('user_id', $this->id)
            ->where('church_id', $churchId)
            ->value('person_id');

        if ($churchId !== null) {
            return $pivotPersonId !== null
                ? Person::query()
                    ->withoutGlobalScopes()
                    ->where('church_id', $churchId)
                    ->find($pivotPersonId)
                : null;
        }

        return $this->person;
    }

    /** @return BelongsToMany<Church, $this> */
    public function churches(): BelongsToMany
    {
        return $this->belongsToMany(Church::class)->withTimestamps();
    }

    /**
     * Members of the current church — use for all user pickers so one
     * church's members never appear in another church's UI.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function inCurrentChurch(Builder $query): Builder
    {
        return $query->whereHas('churches', fn (Builder $inner) => $inner
            ->whereKey(app(CurrentChurch::class)->id()));
    }

    /** @return BelongsTo<Church, $this> */
    public function currentChurch(): BelongsTo
    {
        return $this->belongsTo(Church::class, 'current_church_id');
    }

    /**
     * Switch the user's current church, refusing churches the user is not a
     * member of.
     */
    public function switchChurch(Church $church): bool
    {
        if (! $this->churches()->whereKey($church->id)->exists()) {
            return false;
        }

        $this->forceFill(['current_church_id' => $church->id])->save();
        $this->setRelation('currentChurch', $church);
        $this->resolvedAccessRoles = [];

        return true;
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
        return $this->unreadCommentsQuery()
            ->whereIn('comments.commentable_id', $this->directConversations()->select('conversations.id'))
            ->count();
    }

    /**
     * Base query for comments unread by this user: excludes the user's own
     * comments and comments the user has already read.
     *
     * @return Builder<Comment>
     */
    private function unreadCommentsQuery(): Builder
    {
        return Comment::query()
            ->where('comments.commentable_type', (new Conversation())->getMorphClass())
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
            );
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
            ->whereHasMorph('liturgy', Service::class, $this->constrainToUpcomingServices(...))
            ->with(['liturgy.template', 'content'])
            ->get()
            ->sortBy($this->upcomingAssignmentSortKey(...))
            ->values();
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    private function constrainToUpcomingServices(Builder $query): Builder
    {
        return $query->upcoming();
    }

    private function upcomingAssignmentSortKey(LiturgyElement $element): string
    {
        $liturgy = $element->liturgy;

        if (! $liturgy instanceof Service) {
            throw new LogicException('Expected upcoming assignment liturgy to be a Service.');
        }

        return $liturgy->date->toDateString();
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
        return $this->unreadCommentsQuery()
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
        return $this->unreadCommentsQuery()
            ->whereIn('comments.commentable_id', $this->directConversations()->select('conversations.id'))
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
