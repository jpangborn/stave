<?php

use App\Enums\AccessRole;
use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function (): void {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function (): void {
    $response = Livewire::test('pages::auth.register')
        ->set('church_name', 'Test Community Church')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('a church name is required to register', function (): void {
    Livewire::test('pages::auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['church_name' => 'required']);
});

test('registering creates a church with the registrant as its admin', function (): void {
    Livewire::test('pages::auth.register')
        ->set('church_name', 'Grace Fellowship')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();

    $church = Church::query()->where('name', 'Grace Fellowship')->sole();
    $user = User::query()->where('email', 'test@example.com')->sole();

    expect($church->slug)->toBe('grace-fellowship')
        ->and($user->current_church_id)->toBe($church->id)
        ->and($church->hasMember($user))->toBeTrue()
        ->and($user->hasAccessRole(AccessRole::ADMIN, $church))->toBeTrue()
        ->and($user->person->church_id)->toBe($church->id);
});
