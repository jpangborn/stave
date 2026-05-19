<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('notifications settings page renders for an authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('settings.notifications'))
        ->assertSuccessful()
        ->assertSeeText(__('Push notifications'));
});

test('notifications settings page mounts the preferences sub-component', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('settings.notifications'))
        ->assertSuccessful()
        ->assertSeeText(__('What to notify me about'))
        ->assertSeeText(__('Quiet hours'));
});

test('notifications settings page mounts the push subscription manager sub-component', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('settings.notifications'))
        ->assertSuccessful()
        ->assertSeeText(__('Receive a banner when teammates message you, even when Stave is closed.'));
});
