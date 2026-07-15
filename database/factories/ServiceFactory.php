<?php

namespace Database\Factories;

use App\Models\Service;
use Database\Factories\Concerns\HasChurchAffinity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
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
            'title' => $this->faker->sentence(3),
            'date' => $this->faker->dateTimeBetween('-1 year', '+1 month'),
            'template_id' => null,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
