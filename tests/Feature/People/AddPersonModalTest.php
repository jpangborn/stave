<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

test('saves a new person with name and email', function (): void {
    Livewire::test('people.add-modal')
        ->set('form.first_name', 'Jane')
        ->set('form.last_name', 'Doe')
        ->set('form.email', 'jane.doe@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('person-added');

    expect(Person::where('email', 'jane.doe@example.com')->first())
        ->not->toBeNull()
        ->first_name->toBe('Jane')
        ->last_name->toBe('Doe');
});

test('requires first and last name', function (): void {
    Livewire::test('people.add-modal')
        ->set('form.first_name', '')
        ->set('form.last_name', '')
        ->set('form.email', 'jane@example.com')
        ->call('save')
        ->assertHasErrors(['form.first_name', 'form.last_name']);
});

test('save & open profile dispatches drawer open event', function (): void {
    Livewire::test('people.add-modal')
        ->set('form.first_name', 'Sam')
        ->set('form.last_name', 'Park')
        ->set('form.email', 'sam@example.com')
        ->call('save', true)
        ->assertHasNoErrors()
        ->assertDispatched('open-person-drawer');
});

test('does not create a User account', function (): void {
    $userCountBefore = User::count();

    Livewire::test('people.add-modal')
        ->set('form.first_name', 'Jane')
        ->set('form.last_name', 'Doe')
        ->set('form.email', 'jane.doe@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::count())->toBe($userCountBefore);
});
