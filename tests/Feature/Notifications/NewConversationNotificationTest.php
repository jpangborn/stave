<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewConversationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;

uses(RefreshDatabase::class);

function makeConversationFixture(): array
{
    $author = User::factory()->create(['name' => 'Ada Lovelace']);
    $group = Group::factory()->create(['name' => 'Praise Team']);
    $conversation = Conversation::factory()->for($group)->create([
        'user_id' => $author->id,
        'title' => 'Sunday songs',
    ]);

    return [$conversation, $author];
}

test('new-conversation notification routes through all four channels', function (): void {
    [$conversation, $author] = makeConversationFixture();
    $recipient = User::factory()->create();

    $channels = (new NewConversationNotification($conversation, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database']);
});

test('new-conversation notification builds a broadcast payload', function (): void {
    [$conversation, $author] = makeConversationFixture();
    $recipient = User::factory()->create();

    $message = (new NewConversationNotification($conversation, $author))->toBroadcast($recipient);

    expect($message)->toBeInstanceOf(BroadcastMessage::class);
    expect($message->data)->toHaveKeys(['title', 'body', 'url']);
    expect($message->data['title'])->toContain('Praise Team');
    expect($message->data['body'])->toContain('Ada Lovelace');
    expect($message->data['body'])->toContain('Sunday songs');
    expect($message->data['url'])->toContain('/groups/');
});

test('new-conversation notification builds a webpush message', function (): void {
    [$conversation, $author] = makeConversationFixture();
    $recipient = User::factory()->create();

    $message = (new NewConversationNotification($conversation, $author))->toWebPush($recipient, null);

    expect($message)->toBeInstanceOf(WebPushMessage::class);
});

test('new-conversation notification builds a mail message', function (): void {
    [$conversation, $author] = makeConversationFixture();
    $recipient = User::factory()->create();

    $message = (new NewConversationNotification($conversation, $author))->toMail($recipient);

    expect($message)->toBeInstanceOf(MailMessage::class);
    expect($message->subject)->toContain('Praise Team');
});

test('new-conversation notification stores database payload with type discriminator', function (): void {
    [$conversation, $author] = makeConversationFixture();
    $recipient = User::factory()->create();

    $data = (new NewConversationNotification($conversation, $author))->toArray($recipient);

    expect($data)->toHaveKeys(['type', 'title', 'body', 'url', 'conversation_id', 'group_id', 'author_id']);
    expect($data['type'])->toBe('conversation.created');
    expect($data['conversation_id'])->toBe($conversation->id);
    expect($data['author_id'])->toBe($author->id);
});
