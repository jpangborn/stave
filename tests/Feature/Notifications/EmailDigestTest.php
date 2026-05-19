<?php

declare(strict_types=1);

use App\Enums\DigestFrequency;
use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Mail\NotificationDigestMail;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\EmailDigestItem;
use App\Models\Group;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\ConversationReplyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function makeDigestFixture(): array
{
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hi</p>', $author);

    return [$conversation, $comment, $author];
}

test('a non-mention notification routes mail through the digest channel by default', function (): void {
    [$conversation, $comment, $author] = makeDigestFixture();
    $recipient = User::factory()->create();

    expect($recipient->digest_frequency)->toBe(DigestFrequency::DAILY);

    Notification::send($recipient, new ConversationReplyNotification($conversation, $comment, $author));

    expect(EmailDigestItem::query()->where('user_id', $recipient->id)->count())->toBe(1);
});

test('a mention notification stays on instant mail and does not collect to the digest', function (): void {
    Mail::fake();

    [, $comment, $author] = makeDigestFixture();
    $recipient = User::factory()->create();

    $channels = (new CommentMentionNotification($comment, $author))->via($recipient);

    expect($channels)->toContain('mail')
        ->and($channels)->not->toContain('digest')
        ->and(EmailDigestItem::query()->count())->toBe(0);
});

test('setting digest_frequency to Off restores instant mail for non-mention events', function (): void {
    [$conversation, $comment, $author] = makeDigestFixture();
    $recipient = User::factory()->create(['digest_frequency' => DigestFrequency::OFF->value]);

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database'])
        ->and(EmailDigestItem::query()->count())->toBe(0);
});

test('disabling mail on a non-mention event silences both instant mail and the digest', function (): void {
    [$conversation, $comment, $author] = makeDigestFixture();
    $recipient = User::factory()->create();

    NotificationPreference::factory()->disabled()->create([
        'user_id' => $recipient->id,
        'event_type' => NotificationEventType::CONVERSATION_REPLY,
        'channel' => NotificationChannel::MAIL,
    ]);

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->not->toContain('mail')
        ->and($channels)->not->toContain('digest');

    Notification::send($recipient, new ConversationReplyNotification($conversation, $comment, $author));

    expect(EmailDigestItem::query()->count())->toBe(0);
});

test('the send-digests command emails users on the matching cadence and marks items sent', function (): void {
    Mail::fake();

    $user = User::factory()->create(['digest_frequency' => DigestFrequency::DAILY->value]);
    EmailDigestItem::factory()->count(3)->create(['user_id' => $user->id]);

    $this->artisan('stave:send-digests', ['--frequency' => 'daily'])->assertSuccessful();

    Mail::assertQueued(NotificationDigestMail::class, fn (NotificationDigestMail $mail) => $mail->user->is($user)
        && $mail->items->count() === 3
        && $mail->frequency === DigestFrequency::DAILY);

    expect(EmailDigestItem::query()->whereNull('sent_at')->count())->toBe(0);
});

test('re-running the command does not re-send items already marked sent', function (): void {
    Mail::fake();

    $user = User::factory()->create(['digest_frequency' => DigestFrequency::DAILY->value]);
    EmailDigestItem::factory()->count(2)->create(['user_id' => $user->id]);

    $this->artisan('stave:send-digests', ['--frequency' => 'daily'])->assertSuccessful();
    $this->artisan('stave:send-digests', ['--frequency' => 'daily'])->assertSuccessful();

    Mail::assertQueued(NotificationDigestMail::class, 1);
});

test('daily run does not email weekly users', function (): void {
    Mail::fake();

    $daily = User::factory()->create(['digest_frequency' => DigestFrequency::DAILY->value]);
    $weekly = User::factory()->create(['digest_frequency' => DigestFrequency::WEEKLY->value]);
    EmailDigestItem::factory()->create(['user_id' => $daily->id]);
    EmailDigestItem::factory()->create(['user_id' => $weekly->id]);

    $this->artisan('stave:send-digests', ['--frequency' => 'daily'])->assertSuccessful();

    Mail::assertQueued(NotificationDigestMail::class, fn (NotificationDigestMail $mail) => $mail->user->is($daily));
    Mail::assertNotQueued(NotificationDigestMail::class, fn (NotificationDigestMail $mail) => $mail->user->is($weekly));
});

test('users with no pending items receive nothing', function (): void {
    Mail::fake();

    User::factory()->create(['digest_frequency' => DigestFrequency::DAILY->value]);

    $this->artisan('stave:send-digests', ['--frequency' => 'daily'])->assertSuccessful();

    Mail::assertNothingQueued();
});

test('command rejects an off or unknown frequency', function (): void {
    $this->artisan('stave:send-digests', ['--frequency' => 'off'])->assertFailed();
    $this->artisan('stave:send-digests', ['--frequency' => 'bogus'])->assertFailed();
});

test('new users default to Daily digest frequency', function (): void {
    $user = User::factory()->create();

    expect($user->refresh()->digest_frequency)->toBe(DigestFrequency::DAILY);
});
