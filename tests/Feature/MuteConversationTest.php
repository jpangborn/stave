<?php

declare(strict_types=1);

use App\Enums\GroupMessaging;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\MutedCommentable;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\ConversationReplyNotification;
use App\Notifications\NewConversationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function activateGroupMember(Group $group, User $user): void
{
    $group->allUsers()->attach($user, [
        'role' => 'member',
        'status' => MembershipStatus::ACTIVE,
    ]);
}

test('muting a conversation suppresses ConversationReplyNotification for that user only', function (): void {
    $author = User::factory()->create();
    $muter = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create();
    activateGroupMember($group, $author);
    activateGroupMember($group, $muter);
    activateGroupMember($group, $other);

    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);
    $conversation->postComment('<p>Opening</p>', $author);
    $conversation->postComment('<p>Hi</p>', $muter);
    $conversation->postComment('<p>Hi</p>', $other);

    MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $conversation->id,
    ]);

    Notification::fake();

    $conversation->postComment('<p>Another from author</p>', $author);

    Notification::assertNotSentTo($muter, ConversationReplyNotification::class);
    Notification::assertSentTo($other, ConversationReplyNotification::class);
});

test('muting a conversation does not suppress CommentMentionNotification', function (): void {
    $author = User::factory()->create();
    $muter = User::factory()->create();
    $group = Group::factory()->create();
    activateGroupMember($group, $author);
    activateGroupMember($group, $muter);

    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);

    MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $conversation->id,
    ]);

    Notification::fake();

    $conversation->postComment(
        "<p>Hey <span data-mention=\"{$muter->id}\">@muter</span></p>",
        $author,
    );

    Notification::assertSentTo($muter, CommentMentionNotification::class);
    Notification::assertNotSentTo($muter, ConversationReplyNotification::class);
});

test('unmuting restores ConversationReplyNotification delivery', function (): void {
    $author = User::factory()->create();
    $muter = User::factory()->create();
    $group = Group::factory()->create();
    activateGroupMember($group, $author);
    activateGroupMember($group, $muter);

    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);
    $conversation->postComment('<p>Opening</p>', $author);
    $conversation->postComment('<p>Hi</p>', $muter);

    $mute = MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $conversation->id,
    ]);

    $mute->delete();

    Notification::fake();

    $conversation->postComment('<p>After unmute</p>', $author);

    Notification::assertSentTo($muter, ConversationReplyNotification::class);
});

test('one user muting does not affect notifications for other users', function (): void {
    $author = User::factory()->create();
    $muter = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create();
    activateGroupMember($group, $author);
    activateGroupMember($group, $muter);
    activateGroupMember($group, $other);

    /** @var Conversation $conversation */
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Topic',
        'allow_replies' => true,
    ]);
    $conversation->postComment('<p>Opening</p>', $author);
    $conversation->postComment('<p>Hi</p>', $muter);
    $conversation->postComment('<p>Hi</p>', $other);

    MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $conversation->id,
    ]);

    Notification::fake();

    $conversation->postComment('<p>Author posts again</p>', $author);

    Notification::assertSentTo($other, ConversationReplyNotification::class);
    Notification::assertNotSentTo($muter, ConversationReplyNotification::class);
    Notification::assertNotSentTo($author, ConversationReplyNotification::class);
});

test('muting suppresses NewConversationNotification at create time', function (): void {
    $creator = User::factory()->create();
    $muter = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    activateGroupMember($group, $creator);
    activateGroupMember($group, $muter);
    activateGroupMember($group, $other);

    // Pre-register a mute pointing at the Conversation row the Volt component is
    // about to create. With RefreshDatabase, the next Conversation::id is
    // predictable as max(id) + 1, so this exercises the real notifyMembers()
    // path with an active mute when the conversation is created.
    $predictedConversationId = (int) (Conversation::max('id') ?? 0) + 1;

    MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $predictedConversationId,
    ]);

    Notification::fake();

    $this->actingAs($creator);

    Livewire::test('pages::groups.conversations.create', ['group' => $group])
        ->set('title', 'Heads up')
        ->set('body', '<p>Welcome!</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $conversation = Conversation::where('group_id', $group->id)->firstOrFail();
    expect($conversation->id)->toBe($predictedConversationId);

    Notification::assertNotSentTo($muter, NewConversationNotification::class);
    Notification::assertSentTo($other, NewConversationNotification::class);
    Notification::assertNotSentTo($creator, NewConversationNotification::class);
});

test('mute toggle Livewire component creates and deletes the mute row', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $this->actingAs($user);

    Livewire::test('mute-toggle', [
        'commentable' => $conversation,
        'noun' => 'conversation',
    ])
        ->assertSet('isMuted', false)
        ->call('toggle')
        ->assertSet('isMuted', true);

    expect(MutedCommentable::query()->where([
        'user_id' => $user->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $conversation->id,
    ])->exists())->toBeTrue();

    Livewire::test('mute-toggle', [
        'commentable' => $conversation,
        'noun' => 'conversation',
    ])
        ->assertSet('isMuted', true)
        ->call('toggle')
        ->assertSet('isMuted', false);

    expect(MutedCommentable::query()->where([
        'user_id' => $user->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $conversation->id,
    ])->exists())->toBeFalse();
});

test('mute toggle ignores disallowed commentable types', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('mute-toggle', [
        'commentable' => $other,
        'noun' => 'user',
    ])->call('toggle');

    expect(MutedCommentable::query()->where([
        'user_id' => $user->id,
        'commentable_type' => User::class,
        'commentable_id' => $other->id,
    ])->exists())->toBeFalse();

    expect(MutedCommentable::query()->count())->toBe(0);
});

test('User::hasMuted reflects mute state for a commentable', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    expect($user->hasMuted($conversation))->toBeFalse();

    MutedCommentable::create([
        'user_id' => $user->id,
        'commentable_type' => Conversation::class,
        'commentable_id' => $conversation->id,
    ]);

    expect($user->hasMuted($conversation))->toBeTrue();
});
