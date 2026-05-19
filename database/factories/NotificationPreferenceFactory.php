<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement(NotificationEventType::cases()),
            'channel' => $this->faker->randomElement(NotificationChannel::cases()),
            'enabled' => true,
        ];
    }

    public function disabled(): self
    {
        return $this->state(['enabled' => false]);
    }
}
