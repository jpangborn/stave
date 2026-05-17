<?php

namespace App\Models;

use App\Enums\LiturgyElementType;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Comments\Models\Concerns\HasComments;

/**
 * @property Carbon $date
 */
#[Fillable(['title', 'date', 'template_id', 'notes'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasComments, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return MorphMany<LiturgyElement, $this> */
    public function liturgyElements(): MorphMany
    {
        return $this->morphMany(LiturgyElement::class, 'liturgy')->orderBy(
            'order',
        );
    }

    /**
     * This string will be used in notifications on what a new comment
     * was made.
     */
    public function commentableName(): string
    {
        return 'Service';
    }

    /**
     * This URL will be used in notifications to let the user know
     * where the comment itself can be read.
     */
    public function commentUrl(): string
    {
        return route('services.show', $this);
    }

    /**
     * Get all unique users assigned to liturgy elements in this service.
     *
     * @return Collection<int, User>
     */
    public function assignedUsers(): Collection
    {
        return $this->liturgyElements()
            ->whereNotNull('assignee_id')
            ->with('assignee')
            ->get()
            ->pluck('assignee')
            ->unique('id');
    }

    public function sectionCount(): int
    {
        return $this->liturgyElements
            ->where('type', LiturgyElementType::SECTION)
            ->count();
    }

    public function elementCount(): int
    {
        return $this->liturgyElements
            ->where('type', '!=', LiturgyElementType::SECTION)
            ->count();
    }

    public function unassignedCount(): int
    {
        return $this->liturgyElements
            ->where('type', '!=', LiturgyElementType::SECTION)
            ->whereNull('assignee_id')
            ->count();
    }

    /**
     * Count of elements that should have library content but don't.
     * Excludes section/sermon/prayer/baptism/supper — they either
     * use inline fields or need no content.
     */
    public function missingContentCount(): int
    {
        return $this->liturgyElements
            ->filter(fn (LiturgyElement $el) => $el->requiresContent() && ! $el->hasContent())
            ->count();
    }
}
