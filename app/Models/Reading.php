<?php

namespace App\Models;

use App\Enums\ReadingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Reading extends Model
{
    /** @use HasFactory<\Database\Factories\ReadingFactory> */
    use HasFactory;

    protected $fillable = ["title", "type", "text"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "type" => ReadingType::class,
        ];
    }

    /**
     * @return MorphMany<LiturgyElement,Reading>
     */
    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, "content");
    }
}
