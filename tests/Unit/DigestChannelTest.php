<?php

declare(strict_types=1);

use App\Channels\DigestChannel;
use App\Enums\DigestFrequency;
use App\Enums\NotificationEventType;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\EmailDigestItem;
use App\Models\Group;
use App\Models\User;
use App\Notifications\ConversationReplyNotification;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeReplyForChannel(): array
{
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hello</p>', $author);

    return [$conversation, $comment, $author];
}

test('the digest channel writes an item with notification payload', function (): void {
    [$conversation, $comment, $author] = makeReplyForChannel();
    $recipient = User::factory()->create(['digest_frequency' => DigestFrequency::DAILY->value]);

    (new DigestChannel())->send(
        $recipient,
        new ConversationReplyNotification($conversation, $comment, $author),
    );

    $item = EmailDigestItem::query()->where('user_id', $recipient->id)->sole();

    expect($item->event_type)->toBe(NotificationEventType::CONVERSATION_REPLY)
        ->and($item->data)->toHaveKeys(['title', 'body', 'url', 'conversation_id'])
        ->and($item->sent_at)->toBeNull();
});

test('the digest channel short-circuits when digest_frequency is Off', function (): void {
    [$conversation, $comment, $author] = makeReplyForChannel();
    $recipient = User::factory()->create(['digest_frequency' => DigestFrequency::OFF->value]);

    (new DigestChannel())->send(
        $recipient,
        new ConversationReplyNotification($conversation, $comment, $author),
    );

    expect(EmailDigestItem::query()->count())->toBe(0);
});

test('the digest channel ignores notifications without an eventType method', function (): void {
    $recipient = User::factory()->create(['digest_frequency' => DigestFrequency::DAILY->value]);

    (new DigestChannel())->send($recipient, new TestNotification());

    expect(EmailDigestItem::query()->count())->toBe(0);
});

test('the digest channel ignores non-User notifiables', function (): void {
    [$conversation, $comment, $author] = makeReplyForChannel();
    $anonymous = new class()
    {
        public int $id = 999;
    };

    (new DigestChannel())->send(
        $anonymous,
        new ConversationReplyNotification($conversation, $comment, $author),
    );

    expect(EmailDigestItem::query()->count())->toBe(0);
});
