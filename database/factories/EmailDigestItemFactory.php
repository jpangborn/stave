<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationEventType;
use App\Models\EmailDigestItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailDigestItem>
 */
class EmailDigestItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => NotificationEventType::CONVERSATION_REPLY,
            'data' => [
                'title' => fake()->sentence(4),
                'body' => fake()->sentence(),
                'url' => fake()->url(),
            ],
            'sent_at' => null,
        ];
    }

    public function sent(): self
    {
        return $this->state(['sent_at' => now()]);
    }
}
