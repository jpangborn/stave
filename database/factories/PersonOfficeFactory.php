<?php

namespace Database\Factories;

use App\Enums\Office;
use App\Models\Person;
use App\Models\PersonOffice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonOffice>
 */
class PersonOfficeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'kind' => fake()->randomElement(Office::cases()),
            'started_on' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'ended_on' => null,
            'end_reason' => null,
        ];
    }

    public function ended(?string $reason = null): self
    {
        return $this->state(fn (array $attributes): array => [
            'ended_on' => fake()->dateTimeBetween($attributes['started_on'], 'now'),
            'end_reason' => $reason ?? fake()->sentence(),
        ]);
    }

    public function elder(): self
    {
        return $this->state(['kind' => Office::ELDER]);
    }

    public function deacon(): self
    {
        return $this->state(['kind' => Office::DEACON]);
    }

    public function staff(): self
    {
        return $this->state(['kind' => Office::STAFF]);
    }
}
