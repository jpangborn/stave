<?php

declare(strict_types=1);

use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\ConversationReplyNotification;
use App\Notifications\ServiceDiscussionCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function attachActiveMember(Group $group, User $user): void
{
    $group->allUsers()->attach($user, [
        'role' => 'member',
        'status' => MembershipStatus::ACTIVE,
    ]);
}

test('conversation reply fans out to participants and excludes the author', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $other = User::factory()->create();
    $bystander = User::factory()->create();
    $group = Group::factory()->create();
    attachActiveMember($group, $author);
    attachActiveMember($group, $other);
    attachActiveMember($group, $bystander);

    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);
    $conversation->postComment('<p>Opening</p>', $author);
    $conversation->postComment('<p>Reply by other</p>', $other);

    Notification::fake();

    $conversation->postComment('<p>Second message from author</p>', $author);

    Notification::assertSentTo($other, ConversationReplyNotification::class);
    Notification::assertNotSentTo($author, ConversationReplyNotification::class);
    Notification::assertNotSentTo($bystander, ConversationReplyNotification::class);
});

test('service discussion comment fans out to liturgy assignees and excludes commenter', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $assignee1 = User::factory()->create();
    $assignee2 = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()->assignedTo($assignee1)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);
    LiturgyElement::factory()->assignedTo($assignee2)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);
    LiturgyElement::factory()->assignedTo($author)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);

    $service->comment('<p>A note</p>', $author);

    Notification::assertSentTo($assignee1, ServiceDiscussionCommentNotification::class);
    Notification::assertSentTo($assignee2, ServiceDiscussionCommentNotification::class);
    Notification::assertNotSentTo($author, ServiceDiscussionCommentNotification::class);
});

test('mentioned users get the mention notification and are deduped from the regular fan-out', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $mentioned = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create();
    attachActiveMember($group, $author);
    attachActiveMember($group, $mentioned);
    attachActiveMember($group, $other);

    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);

    $conversation->postComment('<p>Hello</p>', $mentioned);
    $conversation->postComment('<p>Hello</p>', $other);

    Notification::fake();

    $conversation->postComment(
        "<p>Heads up <span data-mention=\"{$mentioned->id}\">@mentioned</span></p>",
        $author,
    );

    Notification::assertSentTo($mentioned, CommentMentionNotification::class);
    Notification::assertNotSentTo($mentioned, ConversationReplyNotification::class);

    Notification::assertSentTo($other, ConversationReplyNotification::class);
    Notification::assertNotSentTo($other, CommentMentionNotification::class);

    Notification::assertNotSentTo($author, CommentMentionNotification::class);
    Notification::assertNotSentTo($author, ConversationReplyNotification::class);
});

test('mentioned user who is not a participant still receives the mention notification', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $mentioned = User::factory()->create();
    $group = Group::factory()->create();
    attachActiveMember($group, $author);
    attachActiveMember($group, $mentioned);

    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);

    $conversation->postComment(
        "<p>Hi <span data-mention=\"{$mentioned->id}\">@mentioned</span></p>",
        $author,
    );

    Notification::assertSentTo($mentioned, CommentMentionNotification::class);
});

test('service discussion comment that mentions an assignee dedupes the regular notification', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()->assignedTo($assignee)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);

    $service->comment(
        "<p>Quick ping <span data-mention=\"{$assignee->id}\">@assignee</span></p>",
        $author,
    );

    Notification::assertSentTo($assignee, CommentMentionNotification::class);
    Notification::assertNotSentTo($assignee, ServiceDiscussionCommentNotification::class);
});
