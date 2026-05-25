<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'address_line1' => fake()->optional(0.6)->streetAddress(),
            'address_city' => fake()->optional(0.6)->city(),
            'address_state' => fake()->optional(0.6)->stateAbbr(),
            'address_zip' => fake()->optional(0.6)->postcode(),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(Gender::cases()),
            'membership_status' => fake()->randomElement([
                MembershipStatus::VISITOR,
                MembershipStatus::ADHERENT,
                MembershipStatus::CATECHUMEN,
                MembershipStatus::MEMBER,
            ]),
            'membership_since' => fake()->optional(0.7)->date(),
        ];
    }

    public function visitor(): self
    {
        return $this->state(['membership_status' => MembershipStatus::VISITOR]);
    }

    public function adherent(): self
    {
        return $this->state(['membership_status' => MembershipStatus::ADHERENT]);
    }

    public function catechumen(): self
    {
        return $this->state(['membership_status' => MembershipStatus::CATECHUMEN]);
    }

    public function member(): self
    {
        return $this->state(['membership_status' => MembershipStatus::MEMBER]);
    }
}
