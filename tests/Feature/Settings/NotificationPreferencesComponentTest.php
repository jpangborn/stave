<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('mount loads a fully-enabled matrix when no rows exist', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->assertSet('matrix.COMMENT_MENTION.WEBPUSH', true)
        ->assertSet('matrix.CONVERSATION_REPLY.MAIL', true)
        ->assertSet('matrix.CONVERSATION_CREATED.DATABASE', true)
        ->assertSet('matrix.SERVICE_DISCUSSION_COMMENT.BROADCAST', true);
});

test('mount reflects an existing disabled row as false', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    NotificationPreference::factory()->disabled()->create([
        'user_id' => $user->id,
        'event_type' => NotificationEventType::CONVERSATION_REPLY,
        'channel' => NotificationChannel::WEBPUSH,
    ]);

    Livewire::test('settings.notification-preferences')
        ->assertSet('matrix.CONVERSATION_REPLY.WEBPUSH', false)
        ->assertSet('matrix.CONVERSATION_REPLY.MAIL', true);
});

test('toggling a switch off persists a disabled preference row', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->set('matrix.CONVERSATION_REPLY.MAIL', false);

    expect(
        $user->notificationPreferences()
            ->where('event_type', NotificationEventType::CONVERSATION_REPLY->value)
            ->where('channel', NotificationChannel::MAIL->value)
            ->where('enabled', false)
            ->exists()
    )->toBeTrue();
});

test('toggling a switch back on deletes the preference row', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    NotificationPreference::factory()->disabled()->create([
        'user_id' => $user->id,
        'event_type' => NotificationEventType::CONVERSATION_REPLY,
        'channel' => NotificationChannel::MAIL,
    ]);

    Livewire::test('settings.notification-preferences')
        ->set('matrix.CONVERSATION_REPLY.MAIL', true);

    expect($user->notificationPreferences()->count())->toBe(0);
});

test('saveQuietHours persists times and timezone to the user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->set('quietHoursStart', '22:00')
        ->set('quietHoursEnd', '07:00')
        ->set('timezone', 'America/Los_Angeles')
        ->call('saveQuietHours')
        ->assertHasNoErrors();

    $user->refresh();

    expect((string) $user->getAttribute('quiet_hours_start'))->toStartWith('22:00');
    expect((string) $user->getAttribute('quiet_hours_end'))->toStartWith('07:00');
    expect($user->getAttribute('timezone'))->toBe('America/Los_Angeles');
});

test('saveQuietHours rejects an invalid time format', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->set('quietHoursStart', '25:99')
        ->call('saveQuietHours')
        ->assertHasErrors('quietHoursStart');
});

test('saveQuietHours rejects start without end', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->set('quietHoursStart', '22:00')
        ->set('quietHoursEnd', null)
        ->call('saveQuietHours')
        ->assertHasErrors('quietHoursEnd');
});

test('saveQuietHours rejects end without start', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->set('quietHoursStart', null)
        ->set('quietHoursEnd', '07:00')
        ->call('saveQuietHours')
        ->assertHasErrors('quietHoursStart');
});

test('saveQuietHours rejects an unknown timezone', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->set('timezone', 'Not/A_Real_Zone')
        ->call('saveQuietHours')
        ->assertHasErrors('timezone');
});

test('clearQuietHours nulls both times', function (): void {
    $user = User::factory()->create([
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
        'timezone' => 'UTC',
    ]);
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->call('clearQuietHours')
        ->assertSet('quietHoursStart', null)
        ->assertSet('quietHoursEnd', null);

    $user->refresh();

    expect($user->getAttribute('quiet_hours_start'))->toBeNull();
    expect($user->getAttribute('quiet_hours_end'))->toBeNull();
});

test('component renders all four event labels and four channel headers', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('settings.notification-preferences')
        ->assertSeeText('@Mentions of me')
        ->assertSeeText('Replies in my conversations')
        ->assertSeeText('New conversations in my groups')
        ->assertSeeText('Comments on services I\'m on')
        ->assertSeeText('Email')
        ->assertSeeText('In-app')
        ->assertSeeText('Push')
        ->assertSeeText('Inbox');
});
