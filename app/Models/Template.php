<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = ["name", "default"];

    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, "liturgy")->orderBy(
            "order"
        );
    }

    /**
     * @return HasMany<Service,Template>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
