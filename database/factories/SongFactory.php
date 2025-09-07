<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Song>
 */
class SongFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'authors' => $this->faker->optional()->name(),
            'ccli_number' => $this->faker->optional()->numerify('#######'),
            'copyright' => $this->faker->optional()->company(),
            'lyrics' => $this->faker->paragraphs(3, true),
        ];
    }
}
