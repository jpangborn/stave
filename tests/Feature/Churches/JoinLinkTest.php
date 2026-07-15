<?php

use App\Enums\AccessRole;
use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the join page renders for a valid token and 404s otherwise', function (): void {
    $church = Church::factory()->withJoinToken()->create(['name' => 'Open Door Chapel']);

    $this->get(route('churches.join', $church->join_token))
        ->assertOk()
        ->assertSee('Open Door Chapel');

    $this->get(route('churches.join', 'bogus-token'))->assertNotFound();
});

test('a rotated token invalidates old links', function (): void {
    $church = Church::factory()->withJoinToken()->create();
    $oldToken = $church->join_token;

    $church->regenerateJoinToken();

    $this->get(route('churches.join', $oldToken))->assertNotFound();
    $this->get(route('churches.join', $church->fresh()->join_token))->assertOk();
});

test('a logged-in user can join through the link with zero roles', function (): void {
    $home = Church::factory()->create();
    $target = Church::factory()->withJoinToken()->create();

    $user = User::factory()->forChurch($home)->create();

    Livewire::actingAs($user)
        ->test('pages::churches.join', ['token' => $target->join_token])
        ->call('join')
        ->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($target->hasMember($user))->toBeTrue()
        ->and($user->current_church_id)->toBe($target->id)
        ->and($user->accessRoles($target))->toBeEmpty();
});

test('a guest can register through the join link and becomes a plain member', function (): void {
    $church = Church::factory()->withJoinToken()->create();

    Livewire::withQueryParams(['join' => $church->join_token])
        ->test('pages::auth.register')
        ->set('name', 'New Member')
        ->set('email', 'member@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'member@example.com')->sole();

    expect($church->hasMember($user))->toBeTrue()
        ->and($user->current_church_id)->toBe($church->id)
        ->and($user->accessRoles($church))->toBeEmpty()
        ->and(Church::query()->count())->toBe(1);

    $this->assertAuthenticated();
});

test('registering through the join link does not require a church name', function (): void {
    $church = Church::factory()->withJoinToken()->create();

    Livewire::withQueryParams(['join' => $church->join_token])
        ->test('pages::auth.register')
        ->set('name', 'New Member')
        ->set('email', 'member@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();
});

test('admins can regenerate and disable the join link', function (): void {
    $church = Church::factory()->withJoinToken()->create();
    $admin = User::factory()->forChurch($church)->create();
    $admin->grantAccessRole(AccessRole::ADMIN, $church);

    $original = $church->join_token;

    Livewire::actingAs($admin)
        ->test('pages::settings.church-invitations')
        ->call('regenerateJoinLink');

    expect($church->fresh()->join_token)->not->toBe($original);

    Livewire::actingAs($admin)
        ->test('pages::settings.church-invitations')
        ->call('disableJoinLink');

    expect($church->fresh()->join_token)->toBeNull();
});
