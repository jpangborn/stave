<?php

namespace Database\Factories\Concerns;

use App\Models\Church;
use App\Support\CurrentChurch;

trait HasChurchAffinity
{
    /**
     * Default church for factory-created rows: the current church when one
     * resolves, else the first existing church, creating one only when none
     * exists. "First church wins" keeps everything in a single-church test
     * inside one church; multi-church tests pass church_id explicitly.
     */
    protected function defaultChurchId(): int
    {
        return app(CurrentChurch::class)->id()
            ?? Church::query()->value('id')
            ?? Church::factory()->create()->id;
    }
}
