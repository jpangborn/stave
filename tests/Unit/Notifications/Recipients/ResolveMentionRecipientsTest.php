<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Recipients\ResolveMentionRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns users referenced via data-mention spans, excluding the author', function (): void {
    $author = User::factory()->create();
    $mentioned = User::factory()->create();
    $extra = User::factory()->create();

    $group = Group::factory()->create();
    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);

    $comment = $conversation->postComment(
        '<p>Hello <span data-mention="'.$mentioned->id.'">@m</span> '
        .'and <span data-mention="'.$author->id.'">@self</span> '
        .'and <span data-mention="'.$extra->id.'">@e</span></p>',
        $author,
    );

    $recipients = (new ResolveMentionRecipients())($comment);
    $ids = $recipients->pluck('id')->all();

    expect($ids)->toContain($mentioned->id, $extra->id);
    expect($ids)->not->toContain($author->id);
});

test('returns empty collection when the comment has no mentions', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);

    $comment = $conversation->postComment('<p>No mentions here</p>', $author);

    $recipients = (new ResolveMentionRecipients())($comment);

    expect($recipients)->toBeEmpty();
});
