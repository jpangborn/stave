<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $fillable = ["title", "date", "template_id", "notes"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "date" => "date",
        ];
    }

    /**
     * @return BelongsTo<Template,Service>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, "liturgy")->orderBy(
            "order"
        );
    }
}
