<?php

namespace Database\Factories;

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => '<p>'.$this->faker->paragraph().'</p>',
            'visibility' => $this->faker->randomElement(GroupVisibility::cases()),
            'messaging' => $this->faker->randomElement(GroupMessaging::cases()),
        ];
    }

    public function public(): self
    {
        return $this->state(['visibility' => GroupVisibility::PUBLIC]);
    }

    public function private(): self
    {
        return $this->state(['visibility' => GroupVisibility::PRIVATE]);
    }

    public function withMembers(int $count = 3, MembershipStatus $status = MembershipStatus::ACTIVE, GroupRole $role = GroupRole::MEMBER): self
    {
        return $this->afterCreating(function (Group $group) use ($count, $status, $role): void {
            User::factory()->count($count)->create()->each(function (User $user) use ($group, $status, $role): void {
                $group->allUsers()->attach($user, [
                    'role' => $role,
                    'status' => $status,
                ]);
            });
        });
    }

    public function withConversation(?User $author = null, ?string $body = null): self
    {
        return $this->afterCreating(function (Group $group) use ($author, $body): void {
            $author ??= $group->members()->first() ?? User::factory()->create();
            /** @var Conversation $conversation */
            $conversation = $group->conversations()->create([
                'user_id' => $author->id,
                'title' => 'Welcome thread',
                'allow_replies' => true,
            ]);
            $conversation->postComment($body ?? '<p>'.$this->faker->sentence().'</p>', $author);
        });
    }
}
