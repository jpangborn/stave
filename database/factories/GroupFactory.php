<?php

namespace Database\Factories;

use App\Enums\GroupMessaging;
use App\Enums\GroupVisibility;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
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
}
