<?php

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

test('renders the households index', function (): void {
    $household = Household::factory()->create(['name' => 'The Baker Household']);
    Person::factory()->inHousehold($household, HouseholdRole::CHILD)->create(['first_name' => 'Mary', 'last_name' => 'Baker']);

    $this->get('/households')
        ->assertOk()
        ->assertSee('The Baker Household')
        ->assertSee('Mary Baker')
        ->assertSee('Child');
});

test('shows empty state when no households exist', function (): void {
    $this->get('/households')->assertOk()->assertSee('No households yet');
});

test('creates a household', function (): void {
    Livewire::test('pages::households.index')
        ->call('openNew')
        ->assertSet('showForm', true)
        ->set('hhName', 'The Alkenbrack Household')
        ->set('hhAddress', '44 Maple Ave')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $household = Household::where('name', 'The Alkenbrack Household')->first();
    expect($household)->not->toBeNull();
    expect($household->address)->toBe('44 Maple Ave');
});

test('requires a name to create a household', function (): void {
    Livewire::test('pages::households.index')
        ->call('openNew')
        ->set('hhName', '')
        ->call('save')
        ->assertHasErrors(['hhName' => 'required']);

    expect(Household::count())->toBe(0);
});

test('edits a household', function (): void {
    $household = Household::factory()->create(['name' => 'Old Name', 'address' => null]);

    Livewire::test('pages::households.index')
        ->call('editHousehold', $household->id)
        ->assertSet('editingId', $household->id)
        ->assertSet('hhName', 'Old Name')
        ->set('hhName', 'New Name')
        ->set('hhAddress', '1 Main St')
        ->call('save')
        ->assertHasNoErrors();

    $household->refresh();
    expect($household->name)->toBe('New Name');
    expect($household->address)->toBe('1 Main St');
});

test('deletes a household and unassigns its members', function (): void {
    $household = Household::factory()->create();
    $member = Person::factory()->inHousehold($household, HouseholdRole::SPOUSE)->create();

    Livewire::test('pages::households.index')
        ->call('deleteHousehold', $household->id);

    expect(Household::count())->toBe(0);

    $member->refresh();
    expect($member->exists)->toBeTrue();
    expect($member->household_id)->toBeNull();
    expect($member->household_role)->toBeNull();
});

test('opening a member dispatches the drawer event', function (): void {
    $household = Household::factory()->create();
    $member = Person::factory()->inHousehold($household)->create();

    Livewire::test('pages::households.index')
        ->call('openPerson', $member->id)
        ->assertDispatched('open-person-drawer');
});
