<?php

namespace App\Models;

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property LiturgyElementType $type
 * @property ReadingType|null $reading_type
 */
class LiturgyElement extends Model
{
    /** @use HasFactory<\Database\Factories\LiturgyElementFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'reading_type',
        'order',
        'name',
        'assignee_id',
        'description',
        'content_type',
        'content_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LiturgyElementType::class,
            'reading_type' => ReadingType::class,
        ];
    }

    /**
     * @return BelongsTo<User,LiturgyElement>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
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

    /**
     * Get the display title for the element, including content title if different.
     */
    public function getDisplayTitle(): string
    {
        if (! $this->hasContent()) {
            return $this->name;
        }

        $contentTitle = $this->getContentTitle();

        if (! $contentTitle || $this->name === $contentTitle) {
            return $this->name;
        }

        return $this->name.': '.$contentTitle;
    }

    /**
     * Get the title/name of the associated content.
     */
    public function getContentTitle(): ?string
    {
        if (! $this->hasContent()) {
            return null;
        }

        $content = $this->content;
        if (! $content) {
            return null;
        }

        if ($this->isSong()) {
            /** @var Song $content */
            return $content->name;
        }

        /** @var Reading $content */
        return $content->title;
    }

    /**
     * Get the text content (lyrics for songs, text for readings).
     */
    public function getContentText(): ?string
    {
        if (! $this->hasContent()) {
            return null;
        }

        $content = $this->content;
        if (! $content) {
            return null;
        }

        if ($this->isSong()) {
            /** @var Song $content */
            return $content->lyrics;
        }

        /** @var Reading $content */
        return $content->text;
    }
}
