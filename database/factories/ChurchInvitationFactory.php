<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\ChurchInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChurchInvitation>
 */
class ChurchInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'email' => fake()->unique()->safeEmail(),
            'roles' => [],
            'invited_by' => User::factory(),
            'token' => Str::random(64),
            'expires_at' => now()->addDays(14),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state(['accepted_at' => now()]);
    }
}
