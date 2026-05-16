<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeGroupConversation(GroupRole $authorRole = GroupRole::MEMBER): array
{
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);

    $author = User::factory()->create();
    $group->allUsers()->attach($author, ['role' => $authorRole, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create(['group_id' => $group->id, 'user_id' => $author->id]);
    $comment = $conversation->postComment('Hello group', $author);

    return [$group, $conversation, $comment->fresh(), $author];
}

// --- Model behavior ---

test('pin sets pinned_at and pinned_by_user_id', function (): void {
    [$group, $conversation, $comment, $author] = makeGroupConversation();

    expect($comment->isPinned())->toBeFalse();

    $comment->pin($author);

    expect($comment->fresh()->isPinned())->toBeTrue()
        ->and($comment->fresh()->pinned_by_user_id)->toBe($author->id)
        ->and($comment->fresh()->pinned_at)->not->toBeNull();
});

test('unpin clears pinned_at and pinned_by_user_id', function (): void {
    [$group, $conversation, $comment, $author] = makeGroupConversation();

    $comment->pin($author);
    $comment->unpin();

    expect($comment->fresh()->isPinned())->toBeFalse()
        ->and($comment->fresh()->pinned_by_user_id)->toBeNull()
        ->and($comment->fresh()->pinned_at)->toBeNull();
});

test('togglePrayer flips is_prayer', function (): void {
    [$group, $conversation, $comment, $author] = makeGroupConversation();

    expect($comment->is_prayer)->toBeFalse();

    $comment->togglePrayer();
    expect($comment->fresh()->is_prayer)->toBeTrue();

    $comment->fresh()->togglePrayer();
    expect($comment->fresh()->is_prayer)->toBeFalse();
});

test('Comment loaded from the database is the App namespace subclass', function (): void {
    [$group, $conversation, $comment] = makeGroupConversation();

    expect($conversation->comments()->first())->toBeInstanceOf(Comment::class);
});

test('Conversation pinnedComments returns only pinned ones, newest first', function (): void {
    [$group, $conversation, $first, $author] = makeGroupConversation();

    $second = $conversation->postComment('second', $author);
    $third = $conversation->postComment('third', $author);

    $first->fresh()->pin($author);
    $this->travel(1)->seconds();
    $third->fresh()->pin($author);

    $pinned = $conversation->pinnedComments()->get();

    expect($pinned)->toHaveCount(2)
        ->and($pinned->first()->id)->toBe($third->id)
        ->and($pinned->last()->id)->toBe($first->id);
});

// --- Policy: pin / unpin ---

test('author of the comment can pin their own comment', function (): void {
    [$group, $conversation, $comment, $author] = makeGroupConversation();

    expect($author->can('pin', $comment))->toBeTrue()
        ->and($author->can('unpin', $comment))->toBeTrue();
});

test('group leader can pin any comment in the conversation', function (): void {
    [$group, $conversation, $comment] = makeGroupConversation();

    $leader = User::factory()->create();
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    expect($leader->can('pin', $comment))->toBeTrue()
        ->and($leader->can('unpin', $comment))->toBeTrue();
});

test('a non-leader member who did not author the comment cannot pin it', function (): void {
    [$group, $conversation, $comment] = makeGroupConversation();

    $otherMember = User::factory()->create();
    $group->allUsers()->attach($otherMember, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    expect($otherMember->can('pin', $comment))->toBeFalse()
        ->and($otherMember->can('unpin', $comment))->toBeFalse();
});

test('a user outside the group cannot pin a comment', function (): void {
    [$group, $conversation, $comment] = makeGroupConversation();

    $outsider = User::factory()->create();

    expect($outsider->can('pin', $comment))->toBeFalse()
        ->and($outsider->can('unpin', $comment))->toBeFalse();
});

// --- Policy: markPrayer ---

test('any active group member can mark prayer on any comment', function (): void {
    [$group, $conversation, $comment] = makeGroupConversation();

    $otherMember = User::factory()->create();
    $group->allUsers()->attach($otherMember, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    expect($otherMember->can('markPrayer', $comment))->toBeTrue();
});

test('a non-member cannot mark prayer', function (): void {
    [$group, $conversation, $comment] = makeGroupConversation();

    $outsider = User::factory()->create();

    expect($outsider->can('markPrayer', $comment))->toBeFalse();
});

test('a pending member cannot mark prayer', function (): void {
    [$group, $conversation, $comment] = makeGroupConversation();

    $pending = User::factory()->create();
    $group->allUsers()->attach($pending, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    expect($pending->can('markPrayer', $comment))->toBeFalse();
});
