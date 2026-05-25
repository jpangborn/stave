<?php

use App\Enums\MembershipStatus;
use App\Enums\Office;
use App\Enums\TerminationReason;
use App\Models\Person;
use App\Models\PersonOffice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('casts membership_status and termination_reason', function (): void {
    $person = Person::factory()->create([
        'membership_status' => MembershipStatus::TERMINATED->value,
        'termination_reason' => TerminationReason::TRANSFERRED->value,
    ]);

    expect($person->refresh()->membership_status)->toBe(MembershipStatus::TERMINATED);
    expect($person->termination_reason)->toBe(TerminationReason::TRANSFERRED);
});

test('offices relationship returns only current offices', function (): void {
    $person = Person::factory()->create();
    PersonOffice::factory()->elder()->create(['person_id' => $person->id]);
    PersonOffice::factory()->deacon()->ended('stepped down')->create(['person_id' => $person->id]);

    $person->refresh()->load(['offices', 'formerOffices', 'allOffices']);

    expect($person->offices)->toHaveCount(1);
    expect($person->offices->first()->kind)->toBe(Office::ELDER);
    expect($person->formerOffices)->toHaveCount(1);
    expect($person->formerOffices->first()->kind)->toBe(Office::DEACON);
    expect($person->allOffices)->toHaveCount(2);
});

test('pastoralCareElder relationship', function (): void {
    $elder = Person::factory()->create();
    $person = Person::factory()->create(['pastoral_care_elder_id' => $elder->id]);

    expect($person->refresh()->pastoralCareElder->id)->toBe($elder->id);
});

test('searchedBy scope matches first/last/email/phone', function (): void {
    Person::factory()->create(['first_name' => 'Joshua', 'last_name' => 'Pangborn', 'email' => 'josh@example.com', 'phone' => '555-0142']);
    Person::factory()->create(['first_name' => 'Andrew', 'last_name' => 'Burnette', 'email' => 'andrew@example.com', 'phone' => '555-0317']);

    expect(Person::query()->searchedBy('Pangborn')->count())->toBe(1);
    expect(Person::query()->searchedBy('josh@')->count())->toBe(1);
    expect(Person::query()->searchedBy('555-0317')->count())->toBe(1);
    expect(Person::query()->searchedBy(null)->count())->toBe(2);
});
