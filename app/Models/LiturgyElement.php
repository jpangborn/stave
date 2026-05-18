<?php

namespace App\Models;

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use App\Observers\LiturgyElementObserver;
use App\Support\SectionTone;
use Database\Factories\LiturgyElementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property LiturgyElementType $type
 * @property ReadingType|null $reading_type
 * @property string|null $section_color
 */
#[ObservedBy([LiturgyElementObserver::class])]
#[Fillable([
    'type',
    'reading_type',
    'order',
    'name',
    'assignee_id',
    'description',
    'section_color',
    'content_type',
    'content_id',
])]
class LiturgyElement extends Model
{
    /** @use HasFactory<LiturgyElementFactory> */
    use HasFactory;

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

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return MorphTo<Model, $this> */
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

    /** @return MorphTo<Model, $this> */
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
     * Whether this element type would normally have library content
     * (song / reading). Sermon uses an inline title, not library content,
     * and prayer/supper/baptism/section have no content.
     */
    public function requiresContent(): bool
    {
        return in_array($this->type, [
            LiturgyElementType::SONG,
            LiturgyElementType::READING,
        ], true);
    }

    /**
     * Tailwind classes for the section's tonal color. Pass through for
     * non-section rows by looking up the parent section's color upstream.
     *
     * @return array{stripe:string, dot:string, swatch:string, soft:string}
     */
    public function sectionToneClasses(): array
    {
        return SectionTone::classesFor($this->section_color);
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
