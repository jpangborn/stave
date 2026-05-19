<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('notifications settings page renders for an authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('settings.notifications'))
        ->assertSuccessful()
        ->assertSeeText(__('Push notifications'));
});

test('storing a subscription persists a push subscription for the user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.notifications')
        ->call('storeSubscription', [
            'endpoint' => 'https://example.com/push/abc',
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
            'contentEncoding' => 'aes128gcm',
        ])
        ->assertHasNoErrors();

    expect($user->pushSubscriptions()->where('endpoint', 'https://example.com/push/abc')->exists())
        ->toBeTrue();
});

test('removing a subscription deletes it for the user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->updatePushSubscription(
        'https://example.com/push/abc',
        'p256dh-key',
        'auth-token',
        'aes128gcm',
    );

    Livewire::test('pages::settings.notifications')
        ->call('removeSubscription', 'https://example.com/push/abc')
        ->assertHasNoErrors();

    expect($user->pushSubscriptions()->where('endpoint', 'https://example.com/push/abc')->exists())
        ->toBeFalse();
});

test('sending a test push dispatches the TestNotification to the current user', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.notifications')
        ->call('sendTest')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, TestNotification::class);
});
