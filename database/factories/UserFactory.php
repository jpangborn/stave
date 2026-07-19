<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $person = Person::factory()->create();

        return [
            'person_id' => $person->id,
            'name' => $person->fullName,
            'email' => $person->email,
            'email_verified_at' => now(),
            'password' => (static::$password ??= Hash::make('password')),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Attach every created user to a church and make it current. Respects an
     * explicit current_church_id (see forChurch()); otherwise the first
     * existing church wins so single-church tests share one church.
     */
    #[\Override]
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $church = $user->current_church_id !== null
                ? Church::query()->findOrFail($user->current_church_id)
                : (Church::query()->orderBy('id')->first() ?? Church::factory()->create());

            $church->addMember($user);
        });
    }

    public function forChurch(Church $church): static
    {
        return $this->state(['current_church_id' => $church->id]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'email_verified_at' => null,
            ],
        );
    }
}
