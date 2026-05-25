<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Enums\TerminationReason;
use App\Models\Traits\HasGravatar;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property ?Gender $gender
 * @property MembershipStatus $membership_status
 * @property ?TerminationReason $termination_reason
 * @property ?Carbon $membership_since
 * @property ?Carbon $last_active_at
 * @property ?Carbon $birth_date
 */
#[Fillable([
    'first_name',
    'last_name',
    'email',
    'phone',
    'address_line1',
    'address_city',
    'address_state',
    'address_zip',
    'birth_date',
    'gender',
    'membership_status',
    'membership_since',
    'termination_reason',
    'pastoral_care_elder_id',
    'last_active_at',
])]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, HasGravatar;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'membership_status' => MembershipStatus::class,
            'membership_since' => 'date',
            'termination_reason' => TerminationReason::class,
            'last_active_at' => 'datetime',
        ];
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasMany<PersonOffice, $this> Current offices (not yet ended). */
    public function offices(): HasMany
    {
        return $this->hasMany(PersonOffice::class)->whereNull('ended_on');
    }

    /** @return HasMany<PersonOffice, $this> Offices that have ended. */
    public function formerOffices(): HasMany
    {
        return $this->hasMany(PersonOffice::class)->whereNotNull('ended_on');
    }

    /** @return HasMany<PersonOffice, $this> All offices, current and former. */
    public function allOffices(): HasMany
    {
        return $this->hasMany(PersonOffice::class);
    }

    /** @return BelongsTo<Person, $this> */
    public function pastoralCareElder(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'pastoral_care_elder_id');
    }

    /** @return Attribute<string, never> */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (
                $value,
                $attributes,
            ) => "{$attributes['first_name']} {$attributes['last_name']}",
        );
    }

    /**
     * @param  Builder<Person>  $query
     */
    public function scopeSearchedBy(Builder $query, ?string $term): void
    {
        if (! $term) {
            return;
        }

        $query->whereAny(
            ['first_name', 'last_name', 'email', 'phone'],
            'like',
            "%{$term}%",
        );
    }
}
