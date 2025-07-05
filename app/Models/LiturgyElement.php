<?php

namespace App\Models;

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LiturgyElement extends Model
{
    /** @use HasFactory<\Database\Factories\ElementFactory> */
    use HasFactory;

    protected $fillable = [
        "type",
        "order",
        "name",
        "assignee_id",
        "description",
        "content_type",
        "content_id",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "type" => LiturgyElementType::class,
            "reading_type" => ReadingType::class,
        ];
    }
    /**
     * @return BelongsTo<User,LiturgyElement>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, "assignee_id");
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

    public function hasContent(): bool
    {
        return $this->content_type && $this->content_id;
    }
}
