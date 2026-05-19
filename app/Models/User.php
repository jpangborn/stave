<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\DigestFrequency;
use App\Enums\MembershipStatus;
use App\Models\Traits\HasGravatar;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
            ->wherePivot('status', MembershipStatus::ACTIVE);
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
}
