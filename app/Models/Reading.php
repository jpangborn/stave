<?php

namespace App\Models;

use App\Enums\ReadingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Reading extends Model
{
    /** @use HasFactory<\Database\Factories\ReadingFactory> */
    use HasFactory;

    protected $fillable = ['title', 'type', 'text'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReadingType::class,
            'last_used_date' => 'date',
        ];
    }

    /**
     * Add last used date from services to the query.
     *
     * @param Builder<Reading> $query
     * @return Builder<Reading>
     */
    public function scopeWithLastUsedDate(Builder $query): Builder
    {
        return $query->selectRaw("readings.*, (
            SELECT MAX(services.date)
            FROM liturgy_elements
            INNER JOIN services ON liturgy_elements.liturgy_id = services.id
              AND liturgy_elements.liturgy_type = 'App\\Models\\Service'
            WHERE liturgy_elements.content_type = 'App\\Models\\Reading'
              AND liturgy_elements.content_id = readings.id
              AND services.date <= ?
        ) as last_used_date", [
            now()->toDateString(),
        ]);
    }

    /**
     * @return MorphMany<LiturgyElement,Reading>
     */
    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, 'content');
    }
}
