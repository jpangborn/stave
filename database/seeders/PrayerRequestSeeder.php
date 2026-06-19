<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Enums\PrayerRequestVisibility;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrayerRequestSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->first();

        // Bias toward members and catechumens — the people most likely to be in the rota.
        $people = Person::query()
            ->whereIn('membership_status', [
                MembershipStatus::MEMBER,
                MembershipStatus::CATECHUMEN,
            ])
            ->inRandomOrder()
            ->limit(20)
            ->get();

        foreach ($people as $person) {
            $count = fake()->numberBetween(0, 3);

            if ($count === 0) {
                continue;
            }

            PrayerRequest::factory()
                ->count($count)
                ->for($person)
                ->state(fn () => [
                    'created_by_user_id' => $author?->id,
                    'visibility' => fake()->boolean(80)
                        ? PrayerRequestVisibility::BULLETIN
                        : PrayerRequestVisibility::PRIVATE,
                    'completed_at' => fake()->boolean(30) ? fake()->dateTimeBetween('-21 days', 'now') : null,
                ])
                ->create();
        }
    }
}
