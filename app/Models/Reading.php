<?php

namespace App\Models;

use App\Enums\ReadingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property ReadingType $type
 */
class Reading extends Model
{
    /** @use HasFactory<\Database\Factories\ReadingFactory> */
    use HasFactory;

    protected $fillable = ['title', 'type', 'text', 'series_id', 'series_order'];

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
     * @param  Builder<Reading>  $query
     * @return Builder<Reading>
     */
    public function scopeWithLastUsedDate(Builder $query): Builder
    {
        return $query->addSelect([
            'last_used_date' => LiturgyElement::query()
                ->selectRaw('MAX(services.date)')
                ->join('services', function ($join) {
                    $join->on('liturgy_elements.liturgy_id', '=', 'services.id')
                        ->where('liturgy_elements.liturgy_type', '=', Service::class);
                })
                ->where('liturgy_elements.content_type', '=', Reading::class)
                ->whereColumn('liturgy_elements.content_id', 'readings.id')
                ->where('services.date', '<=', now()->toDateString()),
        ]);
    }

    /** @return MorphMany<LiturgyElement, $this> */
    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, 'content');
    }

    /** @return BelongsTo<Series, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
