<?php

namespace Database\Factories;

use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PastoralNote>
 */
class PastoralNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'author_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
