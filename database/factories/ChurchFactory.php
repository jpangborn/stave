<?php

namespace Database\Factories;

use App\Models\Church;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Church>
 */
class ChurchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' Community Church';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'timezone' => 'America/New_York',
        ];
    }

    public function withJoinToken(): static
    {
        return $this->state(fn (array $attributes): array => [
            'join_token' => Str::random(64),
            'join_token_rotated_at' => now(),
        ]);
    }
}
