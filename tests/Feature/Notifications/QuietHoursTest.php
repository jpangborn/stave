<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\ConversationReplyNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeQuietHoursFixture(): array
{
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hi</p>', $author);

    return [$conversation, $comment, $author];
}

function makeRecipientInDnd(): User
{
    return User::factory()->create([
        'quiet_hours_start' => '00:00',
        'quiet_hours_end' => '23:59',
        'timezone' => 'UTC',
    ]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-19 12:00:00', 'UTC'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('webpush and broadcast are suppressed during quiet hours for a conversation reply', function (): void {
    [$conversation, $comment, $author] = makeQuietHoursFixture();
    $recipient = makeRecipientInDnd();

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'database']);
});

test('mail is not suppressed during quiet hours', function (): void {
    [$conversation, $comment, $author] = makeQuietHoursFixture();
    $recipient = makeRecipientInDnd();

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toContain('mail');
});

test('database always remains during quiet hours', function (): void {
    [$conversation, $comment, $author] = makeQuietHoursFixture();
    $recipient = makeRecipientInDnd();

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toContain('database');
});

test('mention notifications ignore quiet hours', function (): void {
    [, $comment, $author] = makeQuietHoursFixture();
    $recipient = makeRecipientInDnd();

    $channels = (new CommentMentionNotification($comment, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database']);
});

test('mention notifications still honor per-channel disables during quiet hours', function (): void {
    [, $comment, $author] = makeQuietHoursFixture();
    $recipient = makeRecipientInDnd();

    NotificationPreference::factory()->disabled()->create([
        'user_id' => $recipient->id,
        'event_type' => NotificationEventType::COMMENT_MENTION,
        'channel' => NotificationChannel::WEBPUSH,
    ]);

    $channels = (new CommentMentionNotification($comment, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'broadcast', 'database']);
});

test('outside the quiet hours window all default channels return', function (): void {
    [$conversation, $comment, $author] = makeQuietHoursFixture();
    $recipient = User::factory()->create([
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '23:00',
        'timezone' => 'UTC',
    ]);

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database']);
});
