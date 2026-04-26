<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'default'])]
class Template extends Model
{
    use HasFactory;

    /** @return MorphMany<LiturgyElement, $this> */
    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, 'liturgy')->orderBy(
            'order'
        );
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
