<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('bell renders with the current unread count', function (): void {
    $user = User::factory()->create();
    $user->notify(new TestNotification());
    $user->notify(new TestNotification());

    $this->actingAs($user);

    Livewire::test('notification-bell')
        ->assertSee('2');
});

test('mark-read clears a single notification', function (): void {
    $user = User::factory()->create();
    $user->notify(new TestNotification());
    $notification = $user->fresh()->notifications()->first();

    $this->actingAs($user);

    Livewire::test('notification-bell')
        ->call('markRead', $notification->id);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('mark-all-read clears every unread notification', function (): void {
    $user = User::factory()->create();
    $user->notify(new TestNotification());
    $user->notify(new TestNotification());
    $user->notify(new TestNotification());

    expect($user->fresh()->unreadNotifications()->count())->toBe(3);

    $this->actingAs($user);

    Livewire::test('notification-bell')
        ->call('markAllRead');

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('bell shows empty state when there are no notifications', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('notification-bell')
        ->assertSee('No notifications yet');
});
