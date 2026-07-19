<?php

namespace Database\Factories;

use App\Models\Template;
use Database\Factories\Concerns\HasChurchAffinity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
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
            'name' => fake()->sentence(2),
            'default' => fake()->boolean(),
        ];
    }
}
