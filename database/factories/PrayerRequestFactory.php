<?php

namespace Database\Factories;

use App\Enums\PrayerRequestVisibility;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\User;
use Database\Factories\Concerns\HasChurchAffinity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PrayerRequest>
 */
class PrayerRequestFactory extends Factory
{
    use HasChurchAffinity;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => fn (): int => $this->defaultChurchId(),
            'person_id' => Person::factory(),
            'body' => fake()->sentence(),
            'visibility' => fake()->randomElement(PrayerRequestVisibility::cases()),
            'created_by_user_id' => User::factory(),
            'completed_at' => null,
        ];
    }

    public function bulletin(): self
    {
        return $this->state(['visibility' => PrayerRequestVisibility::BULLETIN]);
    }

    public function private(): self
    {
        return $this->state(['visibility' => PrayerRequestVisibility::PRIVATE]);
    }

    public function open(): self
    {
        return $this->state(['completed_at' => null]);
    }

    public function completed(?string $when = null): self
    {
        return $this->state([
            'completed_at' => $when ? Carbon::parse($when) : fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
