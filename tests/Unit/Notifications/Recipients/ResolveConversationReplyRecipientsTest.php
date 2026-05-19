<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Recipients\ResolveConversationReplyRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns participating commentators except the author', function (): void {
    $author = User::factory()->create();
    $commenter = User::factory()->create();
    $silent = User::factory()->create();

    $group = Group::factory()->create();
    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);

    $conversation->postComment('<p>Open</p>', $author);
    $conversation->postComment('<p>Reply</p>', $commenter);

    $recipients = (new ResolveConversationReplyRecipients())($conversation, $author);
    $ids = $recipients->pluck('id')->all();

    expect($ids)->toContain($commenter->id);
    expect($ids)->not->toContain($author->id);
    expect($ids)->not->toContain($silent->id);
});

test('returns empty collection when only the author has participated', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);
    $conversation->postComment('<p>Solo</p>', $author);

    $recipients = (new ResolveConversationReplyRecipients())($conversation, $author);

    expect($recipients)->toBeEmpty();
});
