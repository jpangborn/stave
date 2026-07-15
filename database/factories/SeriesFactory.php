<?php

namespace Database\Factories;

use App\Models\Series;
use Database\Factories\Concerns\HasChurchAffinity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Series>
 */
class SeriesFactory extends Factory
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
            'description' => '<p>'.$this->faker->paragraph().'</p>',
        ];
    }
}
