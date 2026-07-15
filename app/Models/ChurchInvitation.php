<?php

namespace App\Models;

use App\Enums\AccessRole;
use Database\Factories\ChurchInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property array<int, string> $roles
 * @property Carbon $expires_at
 * @property ?Carbon $accepted_at
 */
#[Fillable(['church_id', 'email', 'roles', 'invited_by', 'token', 'expires_at', 'accepted_at'])]
class ChurchInvitation extends Model
{
    /** @use HasFactory<ChurchInvitationFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Create (or refresh) the invitation for an email address in a church.
     *
     * @param  array<int, AccessRole>  $roles
     */
    public static function createFor(Church $church, string $email, array $roles, User $invitedBy): self
    {
        return self::query()->updateOrCreate([
            'church_id' => $church->id,
            'email' => Str::lower($email),
        ], [
            'roles' => array_map(fn (AccessRole $role): string => $role->value, $roles),
            'invited_by' => $invitedBy->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(14),
            'accepted_at' => null,
        ]);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** @return array<int, AccessRole> */
    public function accessRoles(): array
    {
        return collect($this->roles)
            ->map(fn (string $value) => AccessRole::tryFrom($value))
            ->filter()
            ->values()
            ->all();
    }

    public function acceptUrl(): string
    {
        return route('invitations.accept', $this->token);
    }
}
