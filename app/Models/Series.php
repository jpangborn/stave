<?php

namespace App\Models;

use App\Models\Concerns\BelongsToChurch;
use Database\Factories\SeriesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description'])]
class Series extends Model
{
    /** @use HasFactory<SeriesFactory> */
    use BelongsToChurch, HasFactory;

    /** @return HasMany<Reading, $this> */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class)->orderBy('series_order');
    }
}
