<?php

namespace App\Models\Concerns;

use App\Models\Church;
use App\Models\Scopes\ChurchScope;
use App\Support\CurrentChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes the model to the current church and stamps church_id on creation.
 * Apply to every model that is the root of top-level queries; strictly
 * parent-accessed children inherit their church through the parent.
 *
 * @property ?int $church_id
 */
trait BelongsToChurch
{
    public static function bootBelongsToChurch(): void
    {
        static::addGlobalScope(new ChurchScope());

        static::creating(function (Model $model): void {
            $model->setAttribute(
                'church_id',
                $model->getAttribute('church_id') ?? app(CurrentChurch::class)->id(),
            );
        });
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
