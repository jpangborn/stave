<?php

namespace App\Models;

use App\Enums\LiturgyElementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LiturgyElement extends Model
{
    /** @use HasFactory<\Database\Factories\ElementFactory> */
    use HasFactory;

    protected $fillable = ['type', 'order', 'name', 'description'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LiturgyElementType::class,
        ];
    }

    /**
     * @return MorphTo<Model,LiturgyElement>
     */
    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function isContentful(): bool
    {
        return in_array($this->type, [
            LiturgyElementType::SONG,
            LiturgyElementType::READING,
        ]);
    }

    /**
     * @return MorphTo<Model,LiturgyElement>
     */
    public function liturgy(): MorphTo
    {
        return $this->morphTo();
    }

    public function isSong(): bool
    {
        return $this->type === LiturgyElementType::SONG;
    }

    public function isReading(): bool
    {
        return $this->type === LiturgyElementType::READING;
    }
}
