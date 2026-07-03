<?php

use App\Enums\AccessRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function (): void {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('greeting renders for an authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertSee('Good ');
    $response->assertSee($user->person->first_name);
});

dataset('quick action gating cases', [
    'no roles' => [[], ['New Conversation'], ['New Service', 'Add Song', 'Add Prayer Request', 'Add Pastoral Note']],
    'liturgy user' => [[AccessRole::LITURGY_USER], ['New Service', 'New Conversation'], ['Add Song', 'Add Prayer Request', 'Add Pastoral Note']],
    'liturgy admin' => [[AccessRole::LITURGY_ADMIN], ['New Service', 'Add Song', 'New Conversation'], ['Add Prayer Request', 'Add Pastoral Note']],
    'pastoral care user' => [[AccessRole::PASTORAL_CARE_USER], ['Add Prayer Request', 'Add Pastoral Note', 'New Conversation'], ['New Service', 'Add Song']],
    'admin' => [[AccessRole::ADMIN], ['New Service', 'Add Song', 'Add Prayer Request', 'Add Pastoral Note', 'New Conversation'], []],
]);

test('quick action buttons are gated by access role', function (array $roles, array $visible, array $hidden): void {
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->grantAccessRole($role);
    }

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    foreach ($visible as $label) {
        $response->assertSee($label);
    }

    foreach ($hidden as $label) {
        $response->assertDontSee($label);
    }
})->with('quick action gating cases');

test('new conversation link points to the compose form', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertSee('compose=1');
});
