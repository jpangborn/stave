<?php

namespace Database\Factories;

use App\Models\Song;
use Database\Factories\Concerns\HasChurchAffinity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Song>
 */
class SongFactory extends Factory
{
    use HasChurchAffinity;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => fn (): int => $this->defaultChurchId(),
            'name' => $this->faker->sentence(3),
            'authors' => $this->faker->optional()->name(),
            'ccli_number' => $this->faker->optional()->numerify('#######'),
            'copyright' => $this->faker->optional()->company(),
            'lyrics' => $this->faker->paragraphs(3, true),
        ];
    }
}
