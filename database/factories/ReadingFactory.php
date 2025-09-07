<?php

namespace Database\Factories;

use App\Enums\ReadingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reading>
 */
class ReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(ReadingType::cases()),
            'text' => '<p>'.$this->faker->paragraph().'</p>',
        ];
    }
}
