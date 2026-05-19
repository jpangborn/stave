<?php

declare(strict_types=1);

use App\Listeners\TouchPushSubscriptionLastUsed;
use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Events\NotificationSent;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\WebPushChannel;

uses(RefreshDatabase::class);

test('touches last_used_at on all of the notifiables push subscriptions when a webpush notification is sent', function (): void {
    $user = User::factory()->create();

    $user->updatePushSubscription('https://example.com/push/one', 'p256dh-1', 'auth-1', 'aes128gcm');
    $user->updatePushSubscription('https://example.com/push/two', 'p256dh-2', 'auth-2', 'aes128gcm');

    // Force last_used_at to null so we can see the listener writing it.
    PushSubscription::query()
        ->whereIn('endpoint', ['https://example.com/push/one', 'https://example.com/push/two'])
        ->update(['last_used_at' => null]);

    $listener = new TouchPushSubscriptionLastUsed();
    $listener->handle(new NotificationSent($user, new TestNotification(), WebPushChannel::class));

    $rows = PushSubscription::query()
        ->whereIn('endpoint', ['https://example.com/push/one', 'https://example.com/push/two'])
        ->get();

    expect($rows)->toHaveCount(2);
    foreach ($rows as $row) {
        expect($row->getAttribute('last_used_at'))->not->toBeNull();
    }
});

test('ignores notifications sent over channels other than WebPushChannel', function (): void {
    $user = User::factory()->create();

    $user->updatePushSubscription('https://example.com/push/ignored', 'p256dh', 'auth', 'aes128gcm');

    PushSubscription::query()
        ->where('endpoint', 'https://example.com/push/ignored')
        ->update(['last_used_at' => null]);

    $listener = new TouchPushSubscriptionLastUsed();
    $listener->handle(new NotificationSent($user, new TestNotification(), MailChannel::class));

    $row = PushSubscription::query()
        ->where('endpoint', 'https://example.com/push/ignored')
        ->first();

    expect($row->getAttribute('last_used_at'))->toBeNull();
});

test('does not touch push subscriptions belonging to other users', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->updatePushSubscription('https://example.com/push/a', 'p256dh-a', 'auth-a', 'aes128gcm');
    $userB->updatePushSubscription('https://example.com/push/b', 'p256dh-b', 'auth-b', 'aes128gcm');

    PushSubscription::query()
        ->whereIn('endpoint', ['https://example.com/push/a', 'https://example.com/push/b'])
        ->update(['last_used_at' => null]);

    $listener = new TouchPushSubscriptionLastUsed();
    $listener->handle(new NotificationSent($userA, new TestNotification(), WebPushChannel::class));

    expect(
        PushSubscription::query()->where('endpoint', 'https://example.com/push/a')->first()->getAttribute('last_used_at')
    )->not->toBeNull();

    expect(
        PushSubscription::query()->where('endpoint', 'https://example.com/push/b')->first()->getAttribute('last_used_at')
    )->toBeNull();
});

test('is registered on the NotificationSent event so push notifications keep last_used_at fresh end-to-end', function (): void {
    $user = User::factory()->create();
    $user->updatePushSubscription('https://example.com/push/registered', 'p256dh', 'auth', 'aes128gcm');

    PushSubscription::query()
        ->where('endpoint', 'https://example.com/push/registered')
        ->update(['last_used_at' => null]);

    event(new NotificationSent($user, new TestNotification(), WebPushChannel::class));

    $row = PushSubscription::query()->where('endpoint', 'https://example.com/push/registered')->first();
    expect($row->getAttribute('last_used_at'))->not->toBeNull();
});
