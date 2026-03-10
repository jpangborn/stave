<?php

namespace Database\Seeders;

use App\Enums\GroupRole;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        Group::factory()->count(5)->create()->each(function (Group $group) use ($users) {
            $members = $users->random(min($users->count(), random_int(3, 6)));

            $group->allUsers()->attach($members->first()->id, [
                'role' => GroupRole::LEADER,
                'status' => MembershipStatus::ACTIVE,
            ]);

            $members->skip(1)->each(function (User $user) use ($group) {
                $group->allUsers()->attach($user->id, [
                    'role' => fake()->randomElement(GroupRole::cases()),
                    'status' => fake()->randomElement(MembershipStatus::cases()),
                ]);
            });
        });
    }
}
