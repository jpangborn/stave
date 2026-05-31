<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ServiceDiscussionCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;

uses(RefreshDatabase::class);

function makeDiscussionFixture(): array
{
    $author = User::factory()->create(['name' => 'Linus Torvalds']);
    $service = Service::factory()->create(['title' => 'Easter Service']);
    $service->comment('<p>Notes about <em>introit</em></p>', $author);
    /** @var Comment $comment */
    $comment = $service->comments()->latest('id')->first();

    return [$service, $comment, $author];
}

test('service-discussion notification routes mail through digest for new users by default', function (): void {
    [$service, $comment, $author] = makeDiscussionFixture();
    $recipient = User::factory()->create();

    $channels = (new ServiceDiscussionCommentNotification($service, $comment, $author))->via($recipient);

    expect($channels)->toBe(['digest', 'broadcast', 'webpush', 'database']);
});

test('service-discussion broadcast payload includes service title and discussion anchor', function (): void {
    [$service, $comment, $author] = makeDiscussionFixture();
    $recipient = User::factory()->create();

    $message = (new ServiceDiscussionCommentNotification($service, $comment, $author))->toBroadcast($recipient);

    expect($message)->toBeInstanceOf(BroadcastMessage::class);
    expect($message->data['title'])->toContain('Easter Service');
    expect($message->data['body'])->toContain('Linus Torvalds');
    expect($message->data['body'])->toContain('introit');
    expect($message->data['url'])->toContain('?tab=discussion');
});

test('service-discussion webpush message is well-formed', function (): void {
    [$service, $comment, $author] = makeDiscussionFixture();
    $recipient = User::factory()->create();

    $message = (new ServiceDiscussionCommentNotification($service, $comment, $author))->toWebPush($recipient, null);

    expect($message)->toBeInstanceOf(WebPushMessage::class);
});

test('service-discussion database payload carries type and ids', function (): void {
    [$service, $comment, $author] = makeDiscussionFixture();
    $recipient = User::factory()->create();

    $data = (new ServiceDiscussionCommentNotification($service, $comment, $author))->toArray($recipient);

    expect($data['type'])->toBe('service.discussion.comment');
    expect($data['service_id'])->toBe($service->id);
    expect($data['comment_id'])->toBe($comment->id);
    expect($data['author_id'])->toBe($author->id);
});
