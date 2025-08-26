<?php

namespace Database\Factories;

use App\Enums\LiturgyElementType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LiturgyElement>
 */
class LiturgyElementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(LiturgyElementType::cases()),
            'order' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'assignee_id' => null,
            'content_type' => null,
            'content_id' => null,
            'liturgy_type' => null,
            'liturgy_id' => null,
        ];
    }

    /**
     * State: assign a random user
     */
    /**
     * Assign a random new user
     */
    public function withAssignee(): static
    {
        return $this->state(fn () => [
            'assignee_id' => User::factory(),
        ]);
    }

    /**
     * Assign a specific user
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn () => [
            'assignee_id' => $user->id,
        ]);
    }
}
