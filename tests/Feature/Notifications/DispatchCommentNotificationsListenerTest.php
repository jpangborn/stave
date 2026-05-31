<?php

declare(strict_types=1);

use App\Enums\GroupMembershipStatus;
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
        'status' => GroupMembershipStatus::ACTIVE,
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

    Notification::assertSentToTimes($other, ConversationReplyNotification::class, 1);
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

    Notification::assertSentToTimes($assignee1, ServiceDiscussionCommentNotification::class, 1);
    Notification::assertSentToTimes($assignee2, ServiceDiscussionCommentNotification::class, 1);
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

    Notification::assertSentToTimes($mentioned, CommentMentionNotification::class, 1);
    Notification::assertNotSentTo($mentioned, ConversationReplyNotification::class);

    Notification::assertSentToTimes($other, ConversationReplyNotification::class, 1);
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

    Notification::assertSentToTimes($mentioned, CommentMentionNotification::class, 1);
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

    Notification::assertSentToTimes($assignee, CommentMentionNotification::class, 1);
    Notification::assertNotSentTo($assignee, ServiceDiscussionCommentNotification::class);
});
