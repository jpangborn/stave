<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\NotificationPreference;
use App\Models\Service;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\ConversationReplyNotification;
use App\Notifications\NewConversationNotification;
use App\Notifications\ServiceDiscussionCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeConversationReplyFixture(): array
{
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hi</p>', $author);

    return [$conversation, $comment, $author];
}

test('with no preferences non-mention events route mail to digest by default', function (): void {
    [$conversation, $comment, $author] = makeConversationReplyFixture();
    $recipient = User::factory()->create();

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe(['digest', 'broadcast', 'webpush', 'database']);
});

test('with no preferences mention events keep instant mail', function (): void {
    [, $comment, $author] = makeConversationReplyFixture();
    $recipient = User::factory()->create();

    $channels = (new CommentMentionNotification($comment, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database']);
});

test('with no preferences service discussion routes mail to digest by default', function (): void {
    $author = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>Hi</p>', $author);
    /** @var Comment $comment */
    $comment = $service->comments()->latest('id')->first();
    $recipient = User::factory()->create();

    $channels = (new ServiceDiscussionCommentNotification($service, $comment, $author))->via($recipient);

    expect($channels)->toBe(['digest', 'broadcast', 'webpush', 'database']);
});

test('with no preferences new conversation routes mail to digest by default', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    $recipient = User::factory()->create();

    $channels = (new NewConversationNotification($conversation, $author))->via($recipient);

    expect($channels)->toBe(['digest', 'broadcast', 'webpush', 'database']);
});

test('disabling mail on conversation reply removes mail and digest from the channel list', function (): void {
    [$conversation, $comment, $author] = makeConversationReplyFixture();
    $recipient = User::factory()->create();

    NotificationPreference::factory()->disabled()->create([
        'user_id' => $recipient->id,
        'event_type' => NotificationEventType::CONVERSATION_REPLY,
        'channel' => NotificationChannel::MAIL,
    ]);

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe(['broadcast', 'webpush', 'database']);
});

test('a fully disabled event returns an empty channel list', function (): void {
    [$conversation, $comment, $author] = makeConversationReplyFixture();
    $recipient = User::factory()->create();

    foreach (NotificationChannel::cases() as $channel) {
        NotificationPreference::factory()->disabled()->create([
            'user_id' => $recipient->id,
            'event_type' => NotificationEventType::CONVERSATION_REPLY,
            'channel' => $channel,
        ]);
    }

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe([]);
});

test('non-User notifiables short-circuit to defaults', function (): void {
    [$conversation, $comment, $author] = makeConversationReplyFixture();
    $anonymous = new class()
    {
        public function getKey(): int
        {
            return 0;
        }
    };

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($anonymous);

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database']);
});

test('a disabled channel on one event does not affect a different event', function (): void {
    [$conversation, $comment, $author] = makeConversationReplyFixture();
    $recipient = User::factory()->create();

    NotificationPreference::factory()->disabled()->create([
        'user_id' => $recipient->id,
        'event_type' => NotificationEventType::SERVICE_DISCUSSION_COMMENT,
        'channel' => NotificationChannel::WEBPUSH,
    ]);

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe(['digest', 'broadcast', 'webpush', 'database']);
});
