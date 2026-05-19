<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new NotificationPreferenceService();
});

test('channelsFor returns all defaults when no preference rows exist', function (): void {
    $user = User::factory()->create();

    $channels = $this->service->channelsFor(
        $user,
        NotificationEventType::CONVERSATION_REPLY,
        NotificationEventType::CONVERSATION_REPLY->defaultChannels(),
    );

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database']);
});

test('channelsFor strips a channel when a disabled preference row exists', function (): void {
    $user = User::factory()->create();

    NotificationPreference::factory()
        ->disabled()
        ->create([
            'user_id' => $user->id,
            'event_type' => NotificationEventType::CONVERSATION_REPLY,
            'channel' => NotificationChannel::WEBPUSH,
        ]);

    $channels = $this->service->channelsFor(
        $user,
        NotificationEventType::CONVERSATION_REPLY,
        NotificationEventType::CONVERSATION_REPLY->defaultChannels(),
    );

    expect($channels)->toBe(['mail', 'broadcast', 'database']);
});

test('channelsFor returns an empty array when every channel is disabled', function (): void {
    $user = User::factory()->create();

    foreach (NotificationChannel::cases() as $channel) {
        NotificationPreference::factory()
            ->disabled()
            ->create([
                'user_id' => $user->id,
                'event_type' => NotificationEventType::CONVERSATION_REPLY,
                'channel' => $channel,
            ]);
    }

    $channels = $this->service->channelsFor(
        $user,
        NotificationEventType::CONVERSATION_REPLY,
        NotificationEventType::CONVERSATION_REPLY->defaultChannels(),
    );

    expect($channels)->toBe([]);
});

test('matrixFor enumerates the full grid as all-true when no rows exist', function (): void {
    $user = User::factory()->create();

    $matrix = $this->service->matrixFor($user);

    foreach (NotificationEventType::userConfigurable() as $event) {
        foreach (NotificationChannel::cases() as $channel) {
            expect($matrix[$event->value][$channel->value])->toBeTrue();
        }
    }
});

test('matrixFor reflects disabled rows as false', function (): void {
    $user = User::factory()->create();

    NotificationPreference::factory()
        ->disabled()
        ->create([
            'user_id' => $user->id,
            'event_type' => NotificationEventType::COMMENT_MENTION,
            'channel' => NotificationChannel::MAIL,
        ]);

    $matrix = $this->service->matrixFor($user);

    expect($matrix[NotificationEventType::COMMENT_MENTION->value][NotificationChannel::MAIL->value])->toBeFalse()
        ->and($matrix[NotificationEventType::COMMENT_MENTION->value][NotificationChannel::WEBPUSH->value])->toBeTrue();
});

test('setChannel(false) upserts a disabled row and is idempotent', function (): void {
    $user = User::factory()->create();

    $this->service->setChannel($user, NotificationEventType::CONVERSATION_REPLY, NotificationChannel::WEBPUSH, false);
    $this->service->setChannel($user, NotificationEventType::CONVERSATION_REPLY, NotificationChannel::WEBPUSH, false);

    expect($user->notificationPreferences()->count())->toBe(1);
    expect($user->notificationPreferences()->first()->enabled)->toBeFalse();
});

test('setChannel(true) deletes the row when toggling back to default', function (): void {
    $user = User::factory()->create();
    NotificationPreference::factory()
        ->disabled()
        ->create([
            'user_id' => $user->id,
            'event_type' => NotificationEventType::CONVERSATION_REPLY,
            'channel' => NotificationChannel::WEBPUSH,
        ]);

    $this->service->setChannel($user, NotificationEventType::CONVERSATION_REPLY, NotificationChannel::WEBPUSH, true);

    expect($user->notificationPreferences()->count())->toBe(0);
});

test('isInQuietHours returns false when DND is not configured', function (): void {
    $user = User::factory()->create();

    expect($this->service->isInQuietHours($user))->toBeFalse();
});

test('isInQuietHours returns false when only start is set', function (): void {
    $user = User::factory()->create([
        'quiet_hours_start' => '22:00',
    ]);

    expect($this->service->isInQuietHours($user))->toBeFalse();
});

test('isInQuietHours returns false when start equals end', function (): void {
    $user = User::factory()->create([
        'quiet_hours_start' => '08:00',
        'quiet_hours_end' => '08:00',
        'timezone' => 'UTC',
    ]);

    expect($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 08:00:00', 'UTC')))->toBeFalse();
});

test('isInQuietHours handles a same-day window', function (): void {
    $user = User::factory()->create([
        'quiet_hours_start' => '13:00',
        'quiet_hours_end' => '14:00',
        'timezone' => 'UTC',
    ]);

    expect($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 13:30:00', 'UTC')))->toBeTrue()
        ->and($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 12:59:00', 'UTC')))->toBeFalse()
        ->and($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 14:00:00', 'UTC')))->toBeFalse();
});

test('isInQuietHours handles a cross-midnight window', function (): void {
    $user = User::factory()->create([
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
        'timezone' => 'UTC',
    ]);

    expect($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 23:00:00', 'UTC')))->toBeTrue()
        ->and($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 03:00:00', 'UTC')))->toBeTrue()
        ->and($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 12:00:00', 'UTC')))->toBeFalse()
        ->and($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 07:00:00', 'UTC')))->toBeFalse();
});

test('isInQuietHours respects the user timezone', function (): void {
    $user = User::factory()->create([
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
        'timezone' => 'America/Los_Angeles',
    ]);

    // 06:00 UTC on May 19 = 23:00 May 18 LA → inside window
    expect($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 06:00:00', 'UTC')))->toBeTrue();

    // 18:00 UTC = 11:00 LA → outside window
    expect($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 18:00:00', 'UTC')))->toBeFalse();
});

test('isInQuietHours falls back to app timezone when user timezone is null', function (): void {
    config(['app.timezone' => 'UTC']);

    $user = User::factory()->create([
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
        'timezone' => null,
    ]);

    expect($this->service->isInQuietHours($user, CarbonImmutable::parse('2026-05-19 23:00:00', 'UTC')))->toBeTrue();
});
