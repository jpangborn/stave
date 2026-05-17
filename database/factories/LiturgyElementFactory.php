<?php

namespace Database\Factories;

use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Models\User;
use App\Support\SectionTone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiturgyElement>
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
            'type' => $this->faker->randomElement(LiturgyElementType::cases()),
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

    /**
     * State: a section element with a tonal color derived from its name.
     */
    public function section(?string $name = null): static
    {
        return $this->state(function () use ($name) {
            $resolvedName = $name ?? $this->faker->word();

            return [
                'type' => LiturgyElementType::SECTION,
                'name' => $resolvedName,
                'section_color' => SectionTone::pick($resolvedName),
            ];
        });
    }
}
