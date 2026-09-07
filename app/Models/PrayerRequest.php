<?php

namespace App\Models;

use App\Enums\PrayerRequestVisibility;
use App\Models\Concerns\BelongsToChurch;
use Database\Factories\PrayerRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PrayerRequestVisibility $visibility
 * @property ?Carbon $completed_at
 */
#[Fillable([
    'person_id',
    'body',
    'visibility',
    'created_by_user_id',
    'completed_at',
])]
class PrayerRequest extends Model
{
    /** @use HasFactory<PrayerRequestFactory> */
    use BelongsToChurch, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => PrayerRequestVisibility::class,
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null;
    }

    /**
     * @param  Builder<PrayerRequest>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    /**
     * @param  Builder<PrayerRequest>  $query
     */
    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    /**
     * @param  Builder<PrayerRequest>  $query
     */
    #[Scope]
    protected function bulletin(Builder $query): void
    {
        $query->where('visibility', PrayerRequestVisibility::BULLETIN);
    }
}
