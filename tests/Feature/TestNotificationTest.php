<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;

uses(RefreshDatabase::class);

test('test notification routes through broadcast, webpush, and database channels', function (): void {
    $user = User::factory()->create();

    $channels = (new TestNotification())->via($user);

    expect($channels)->toBe(['broadcast', 'webpush', 'database']);
});

test('test notification provides a broadcast payload', function (): void {
    $user = User::factory()->create();

    $message = (new TestNotification())->toBroadcast($user);

    expect($message)->toBeInstanceOf(BroadcastMessage::class);
    expect($message->data)->toMatchArray([
        'title' => 'Push test',
    ]);
    expect($message->data)->toHaveKey('body');
    expect($message->data)->toHaveKey('url');
});

test('test notification provides a webpush payload', function (): void {
    $user = User::factory()->create();

    $message = (new TestNotification())->toWebPush($user, null);

    expect($message)->toBeInstanceOf(WebPushMessage::class);
});

test('test notification stores database payload', function (): void {
    $user = User::factory()->create();

    $data = (new TestNotification())->toArray($user);

    expect($data)->toHaveKeys(['title', 'body', 'url']);
    expect($data['title'])->toBe('Push test');
});
