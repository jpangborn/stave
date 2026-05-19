<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Service;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;

uses(RefreshDatabase::class);

test('mention notification routes through all four channels', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hi <span data-mention="1">@you</span></p>', $author);
    $recipient = User::factory()->create();

    $channels = (new CommentMentionNotification($comment, $author))->via($recipient);

    expect($channels)->toBe(['mail', 'broadcast', 'webpush', 'database']);
});

test('mention notification sets a stable tag and requireInteraction on the webpush message', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hey</p>', $author);
    $recipient = User::factory()->create();

    $message = (new CommentMentionNotification($comment, $author))->toWebPush($recipient, null);

    expect($message)->toBeInstanceOf(WebPushMessage::class);

    $array = $message->toArray();
    expect($array['tag'])->toBe('mention-'.$comment->id);
    expect($array['requireInteraction'])->toBeTrue();
});

test('mention notification url for conversation comment anchors to the comment', function (): void {
    $author = User::factory()->create(['name' => 'Marie Curie']);
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id, 'title' => 'Topic']);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hi</p>', $author);
    $recipient = User::factory()->create();

    $message = (new CommentMentionNotification($comment, $author))->toBroadcast($recipient);

    expect($message)->toBeInstanceOf(BroadcastMessage::class);
    expect($message->data['title'])->toBe('Marie Curie mentioned you');
    expect($message->data['url'])->toContain('#comment-'.$comment->id);
});

test('mention notification url for service comment points to discussion anchor', function (): void {
    $author = User::factory()->create();
    $service = Service::factory()->create(['title' => 'Evening']);
    $service->comment('<p>Hello</p>', $author);
    /** @var Comment $comment */
    $comment = $service->comments()->latest('id')->first();
    $recipient = User::factory()->create();

    $message = (new CommentMentionNotification($comment, $author))->toBroadcast($recipient);

    expect($message->data['url'])->toContain('#discussion');
});

test('mention notification mail subject is prefixed', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hey</p>', $author);
    $recipient = User::factory()->create();

    $mail = (new CommentMentionNotification($comment, $author))->toMail($recipient);

    expect($mail->subject)->toStartWith('[Mention]');
});

test('mention notification database payload carries type and commentable references', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    $conversation = Conversation::factory()->for($group)->create(['user_id' => $author->id]);
    /** @var Comment $comment */
    $comment = $conversation->postComment('<p>Hey</p>', $author);
    $recipient = User::factory()->create();

    $data = (new CommentMentionNotification($comment, $author))->toArray($recipient);

    expect($data['type'])->toBe('comment.mention');
    expect($data['comment_id'])->toBe($comment->id);
    expect($data['commentable_type'])->toBe(Conversation::class);
    expect($data['commentable_id'])->toBe($conversation->id);
});
