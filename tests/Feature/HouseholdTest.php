<?php

use App\Enums\Gender;
use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a household has many people', function (): void {
    $household = Household::factory()->create();
    Person::factory()->count(3)->inHousehold($household)->create();
    Person::factory()->create(['household_id' => null]);

    expect($household->refresh()->people)->toHaveCount(3);
});

test('person casts household_role and belongs to a household', function (): void {
    $household = Household::factory()->create(['name' => 'The Baker Household']);
    $person = Person::factory()->inHousehold($household, HouseholdRole::CHILD)->create();

    $person->refresh();
    expect($person->household_role)->toBe(HouseholdRole::CHILD);
    expect($person->household->name)->toBe('The Baker Household');
});

test('deleting a household unassigns members without deleting them', function (): void {
    $household = Household::factory()->create();
    $members = Person::factory()->count(2)->inHousehold($household, HouseholdRole::SPOUSE)->create();

    $household->delete();

    foreach ($members as $member) {
        $member->refresh();
        expect($member->exists)->toBeTrue();
        expect($member->household_id)->toBeNull();
        expect($member->household_role)->toBeNull();
    }

    expect(Person::count())->toBe(2);
    expect(Household::count())->toBe(0);
});

test('gender enum exposes prefer not to say', function (): void {
    expect(Gender::PREFER_NOT_TO_SAY->label())->toBe('Prefer not to say');
});
