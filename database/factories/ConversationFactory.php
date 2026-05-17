<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'allow_replies' => true,
            'last_comment_at' => null,
        ];
    }

    public function repliesDisabled(): self
    {
        return $this->state(['allow_replies' => false]);
    }
}
