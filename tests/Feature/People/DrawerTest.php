<?php

use App\Enums\AccessRole;
use App\Enums\MembershipStatus;
use App\Enums\Office;
use App\Enums\TerminationReason;
use App\Models\Person;
use App\Models\PersonOffice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

test('opens drawer for a person', function (): void {
    $person = Person::factory()->member()->create(['first_name' => 'Joshua', 'last_name' => 'Pangborn']);

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->assertSet('personId', $person->id)
        ->assertSet('form.first_name', 'Joshua')
        ->assertSet('form.last_name', 'Pangborn');
});

test('saves contact changes', function (): void {
    $person = Person::factory()->visitor()->create(['phone' => null]);

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->set('form.phone', '(206) 555-0142')
        ->set('form.address_city', 'Seattle')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('person-saved');

    $person->refresh();
    expect($person->phone)->toBe('(206) 555-0142');
    expect($person->address_city)->toBe('Seattle');
});

test('changes membership status', function (): void {
    $person = Person::factory()->visitor()->create();

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->set('form.membership_status', MembershipStatus::MEMBER->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($person->refresh()->membership_status)->toBe(MembershipStatus::MEMBER);
});

test('terminating records reason and clears it when status reverted', function (): void {
    $person = Person::factory()->member()->create();

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->set('form.membership_status', MembershipStatus::TERMINATED->value)
        ->set('form.termination_reason', TerminationReason::TRANSFERRED->value)
        ->call('save')
        ->assertHasNoErrors();

    $person->refresh();
    expect($person->membership_status)->toBe(MembershipStatus::TERMINATED);
    expect($person->termination_reason)->toBe(TerminationReason::TRANSFERRED);

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->set('form.membership_status', MembershipStatus::ADHERENT->value)
        ->call('save');

    $person->refresh();
    expect($person->membership_status)->toBe(MembershipStatus::ADHERENT);
    expect($person->termination_reason)->toBeNull();
});

test('adds a new office', function (): void {
    $person = Person::factory()->member()->create();

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->call('addOffice', Office::ELDER->value)
        ->assertHasNoErrors();

    expect($person->offices()->count())->toBe(1);
    expect($person->offices()->first()->kind)->toBe(Office::ELDER);
});

test('ends a current office', function (): void {
    $person = Person::factory()->member()->create();
    $office = PersonOffice::factory()->elder()->create(['person_id' => $person->id]);

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->call('endOffice', $office->id);

    $office->refresh();
    expect($office->ended_on)->not->toBeNull();
    expect($person->offices()->count())->toBe(0);
    expect($person->formerOffices()->count())->toBe(1);
});

test('toggles an access role on the linked user', function (): void {
    $person = Person::factory()->member()->create();
    User::factory()->create(['person_id' => $person->id]);

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->call('toggleAccessRole', AccessRole::ADMIN->value);

    expect($person->fresh()->user->hasAccessRole(AccessRole::ADMIN))->toBeTrue();

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->call('toggleAccessRole', AccessRole::ADMIN->value);

    expect($person->fresh()->user->hasAccessRole(AccessRole::ADMIN))->toBeFalse();
});

test('assigns a pastoral care elder', function (): void {
    $elder = Person::factory()->member()->create();
    PersonOffice::factory()->elder()->create(['person_id' => $elder->id]);

    $person = Person::factory()->visitor()->create();

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->set('form.pastoral_care_elder_id', $elder->id)
        ->call('save');

    expect($person->refresh()->pastoral_care_elder_id)->toBe($elder->id);
});

test('deletes a person', function (): void {
    $person = Person::factory()->visitor()->create();

    Livewire::test('people.drawer')
        ->call('openPerson', $person->id)
        ->call('delete')
        ->assertDispatched('person-deleted');

    expect(Person::find($person->id))->toBeNull();
});
