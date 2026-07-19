<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\HouseholdRole;
use App\Enums\MembershipStatus;
use App\Enums\TerminationReason;
use App\Models\Concerns\BelongsToChurch;
use App\Models\Traits\HasGravatar;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property ?Gender $gender
 * @property MembershipStatus $membership_status
 * @property ?TerminationReason $termination_reason
 * @property ?Carbon $membership_since
 * @property ?Carbon $last_active_at
 * @property ?Carbon $birth_date
 * @property bool $baptized
 * @property ?Carbon $baptism_date
 * @property ?int $household_id
 * @property ?HouseholdRole $household_role
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
    'baptized',
    'baptism_date',
    'household_id',
    'household_role',
    'membership_status',
    'membership_since',
    'termination_reason',
    'pastoral_care_elder_id',
    'last_active_at',
])]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use BelongsToChurch, HasFactory, HasGravatar;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'baptized' => 'boolean',
            'baptism_date' => 'date',
            'household_role' => HouseholdRole::class,
            'membership_status' => MembershipStatus::class,
            'membership_since' => 'date',
            'termination_reason' => TerminationReason::class,
            'last_active_at' => 'datetime',
            'last_noted_at' => 'datetime',
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

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<Person, $this> */
    public function pastoralCareElder(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'pastoral_care_elder_id');
    }

    /** @return HasMany<Person, $this> People who have this person assigned as their pastoral care elder. */
    public function assignedCongregants(): HasMany
    {
        return $this->hasMany(Person::class, 'pastoral_care_elder_id');
    }

    /** @return HasMany<PrayerRequest, $this> */
    public function prayerRequests(): HasMany
    {
        return $this->hasMany(PrayerRequest::class);
    }

    /** @return HasMany<PastoralNote, $this> */
    public function pastoralNotes(): HasMany
    {
        return $this->hasMany(PastoralNote::class);
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
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $query->whereAny(
            ['first_name', 'last_name', 'email', 'phone'],
            'like',
            "%{$term}%",
        );
    }

    /**
     * Add last pastoral note date to the query.
     *
     * @param  Builder<Person>  $query
     * @return Builder<Person>
     */
    #[Scope]
    protected function withLastPastoralNoteDate(Builder $query): Builder
    {
        return $query->addSelect([
            'last_noted_at' => PastoralNote::query()
                ->selectRaw('MAX(created_at)')
                ->whereColumn('pastoral_notes.person_id', 'people.id'),
        ]);
    }

    /**
     * Filter people whose next birthday falls within the given number of days.
     *
     * Designed for windows under a year; at $days >= ~365 the month-day
     * window collapses (from/to wrap to the same date) and stops filtering.
     *
     * @param  Builder<Person>  $query
     * @return Builder<Person>
     */
    #[Scope]
    protected function birthdayWithin(Builder $query, int $days = 30): Builder
    {
        $from = today()->format('m-d');
        $to = today()->addDays($days)->format('m-d');
        $expression = self::monthDayExpression($query->getModel()->getConnection()->getDriverName());

        return $query->whereNotNull('birth_date')
            ->where(function (Builder $q) use ($expression, $from, $to): void {
                if ($from <= $to) {
                    $q->whereRaw("{$expression} BETWEEN ? AND ?", [$from, $to]);
                } else { // window wraps the new year (e.g. Dec 20 → Jan 19)
                    $q->whereRaw("{$expression} >= ?", [$from])
                        ->orWhereRaw("{$expression} <= ?", [$to]);
                }
            });
    }

    /**
     * SQL expression that extracts a zero-padded "MM-DD" string from
     * birth_date, for the database drivers this app runs on (SQLite
     * locally/in tests, MySQL in production per config/deploy.yml).
     */
    private static function monthDayExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "strftime('%m-%d', birth_date)",
            'mysql' => "DATE_FORMAT(birth_date, '%m-%d')",
            default => throw new LogicException("birthdayWithin() has no month-day expression for the [{$driver}] database driver."),
        };
    }
}
