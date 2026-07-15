<?php

namespace Database\Factories;

use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\User;
use Database\Factories\Concerns\HasChurchAffinity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PastoralNote>
 */
class PastoralNoteFactory extends Factory
{
    use HasChurchAffinity;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => fn (): int => $this->defaultChurchId(),
            'person_id' => Person::factory(),
            'author_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
