<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Song extends Model
{
    /** @use HasFactory<\Database\Factories\SongFactory> */
    use HasFactory;

    protected $fillable = ['name', 'authors', 'ccli_number', 'copyright', 'lyrics'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_date' => 'date',
        ];
    }

    /**
     * Add last used date from services to the query.
     *
     * @param  Builder<Song>  $query
     * @return Builder<Song>
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
                ->where('liturgy_elements.content_type', '=', Song::class)
                ->whereColumn('liturgy_elements.content_id', 'songs.id')
                ->where('services.date', '<=', now()->toDateString()),
        ]);
    }

    /** @return HasMany<Recording, $this> */
    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    /** @return HasMany<Sheet, $this> */
    public function sheets(): HasMany
    {
        return $this->hasMany(Sheet::class);
    }

    /** @return MorphMany<LiturgyElement, $this> */
    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, 'content');
    }
}
