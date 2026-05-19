<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use NotificationChannels\WebPush\PushSubscription;

uses(RefreshDatabase::class);

const CHROME_MAC_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

test('storeSubscription persists the user agent and last_used_at', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.push-subscription-manager')
        ->call('storeSubscription', [
            'endpoint' => 'https://example.com/push/abc',
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
            'contentEncoding' => 'aes128gcm',
        ], CHROME_MAC_UA)
        ->assertHasNoErrors();

    $row = PushSubscription::query()
        ->where('endpoint', 'https://example.com/push/abc')
        ->first();

    expect($row)->not->toBeNull();
    expect($row->getAttribute('user_agent'))->toBe(CHROME_MAC_UA);
    expect($row->getAttribute('last_used_at'))->not->toBeNull();
});

test('storeSubscription tolerates a missing user agent', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.push-subscription-manager')
        ->call('storeSubscription', [
            'endpoint' => 'https://example.com/push/no-ua',
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
            'contentEncoding' => 'aes128gcm',
        ])
        ->assertHasNoErrors();

    $row = PushSubscription::query()
        ->where('endpoint', 'https://example.com/push/no-ua')
        ->first();

    expect($row)->not->toBeNull();
    expect($row->getAttribute('user_agent'))->toBeNull();
});

test('storeSubscription is a no-op when no endpoint is provided', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.push-subscription-manager')
        ->call('storeSubscription', [], CHROME_MAC_UA)
        ->assertHasNoErrors();

    expect($user->pushSubscriptions()->count())->toBe(0);
});

test('removeSubscription deletes the row for the user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->updatePushSubscription(
        'https://example.com/push/remove-me',
        'p256dh-key',
        'auth-token',
        'aes128gcm',
    );

    Livewire::test('settings.push-subscription-manager')
        ->call('removeSubscription', 'https://example.com/push/remove-me')
        ->assertHasNoErrors();

    expect($user->pushSubscriptions()->where('endpoint', 'https://example.com/push/remove-me')->exists())
        ->toBeFalse();
});

test('sendTest dispatches the TestNotification to the current user', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.push-subscription-manager')
        ->call('sendTest')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, TestNotification::class);
});

test('list renders the device label parsed from the user agent', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->updatePushSubscription(
        'https://example.com/push/labelled',
        'p256dh-key',
        'auth-token',
        'aes128gcm',
    );

    PushSubscription::query()
        ->where('endpoint', 'https://example.com/push/labelled')
        ->update(['user_agent' => CHROME_MAC_UA]);

    Livewire::test('settings.push-subscription-manager')
        ->assertSeeText('Chrome on macOS');
});

test('list falls back to the truncated endpoint when user_agent is null', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->updatePushSubscription(
        'https://example.com/push/legacy-no-ua-aaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'p256dh-key',
        'auth-token',
        'aes128gcm',
    );

    Livewire::test('settings.push-subscription-manager')
        ->assertSeeText('https://example.com/push/legacy-no-ua')
        ->assertDontSeeText('Chrome on macOS');
});
