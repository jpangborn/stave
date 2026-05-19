<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Notifications\ConversationReplyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;

uses(RefreshDatabase::class);

function makeReplyFixture(): array
{
    $author = User::factory()->create(['name' => 'Grace Hopper']);
    $group = Group::factory()->create(['name' => 'Choir']);
    $conversation = Conversation::factory()->for($group)->create([
        'user_id' => $author->id,
        'title' => 'Rehearsal notes',
    ]);
    $comment = $conversation->postComment('<p>Hello <b>there</b></p>', $author);

    return [$conversation, $comment, $author];
}

test('conversation-reply notification routes mail through digest for new users by default', function (): void {
    [$conversation, $comment, $author] = makeReplyFixture();
    $recipient = User::factory()->create();

    $channels = (new ConversationReplyNotification($conversation, $comment, $author))->via($recipient);

    expect($channels)->toBe(['digest', 'broadcast', 'webpush', 'database']);
});

test('conversation-reply broadcast payload includes title, body preview, and anchored url', function (): void {
    [$conversation, $comment, $author] = makeReplyFixture();
    $recipient = User::factory()->create();

    $message = (new ConversationReplyNotification($conversation, $comment, $author))->toBroadcast($recipient);

    expect($message)->toBeInstanceOf(BroadcastMessage::class);
    expect($message->data['title'])->toContain('Rehearsal notes');
    expect($message->data['body'])->toContain('Grace Hopper');
    expect($message->data['body'])->toContain('Hello there');
    expect($message->data['url'])->toContain('#comment-'.$comment->id);
});

test('conversation-reply webpush message is well-formed', function (): void {
    [$conversation, $comment, $author] = makeReplyFixture();
    $recipient = User::factory()->create();

    $message = (new ConversationReplyNotification($conversation, $comment, $author))->toWebPush($recipient, null);

    expect($message)->toBeInstanceOf(WebPushMessage::class);
});

test('conversation-reply database payload carries type and comment id', function (): void {
    [$conversation, $comment, $author] = makeReplyFixture();
    $recipient = User::factory()->create();

    $data = (new ConversationReplyNotification($conversation, $comment, $author))->toArray($recipient);

    expect($data['type'])->toBe('conversation.reply');
    expect($data['comment_id'])->toBe($comment->id);
    expect($data['conversation_id'])->toBe($conversation->id);
});
