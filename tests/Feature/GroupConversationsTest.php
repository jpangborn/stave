<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewConversationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** @group groups */
function attachMember(Group $group, User $user, GroupRole $role = GroupRole::MEMBER): void
{
    $group->allUsers()->attach($user, [
        'role' => $role,
        'status' => MembershipStatus::ACTIVE,
    ]);
}

// --- Tab visibility on the group show page ---

test('conversations tab is hidden when messaging is off', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::OFF,
    ]);
    attachMember($group, $user);

    $this->actingAs($user)
        ->get("/groups/{$group->id}")
        ->assertSuccessful()
        ->assertDontSee('Conversations');
});

test('conversations tab is hidden for non-members of a public group', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);

    $this->actingAs($user)
        ->get("/groups/{$group->id}")
        ->assertSuccessful()
        ->assertDontSee('Conversations');
});

test('conversations tab is visible to members when messaging is on', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $user);

    $this->actingAs($user)
        ->get("/groups/{$group->id}")
        ->assertSuccessful()
        ->assertSee('Conversations');
});

// --- Create page access (policy::create) ---

test('non-members cannot access the conversation create page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);

    $this->actingAs($user)
        ->get(route('groups.conversations.create', $group))
        ->assertForbidden();
});

test('members cannot access the create page when messaging is off', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::OFF,
    ]);
    attachMember($group, $user);

    $this->actingAs($user)
        ->get(route('groups.conversations.create', $group))
        ->assertForbidden();
});

test('members can access the create page when messaging is open to all members', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $user);

    $this->actingAs($user)
        ->get(route('groups.conversations.create', $group))
        ->assertSuccessful();
});

test('non-leader members cannot access the create page when messaging is leaders only', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ONLY_LEADERS,
    ]);
    attachMember($group, $user);

    $this->actingAs($user)
        ->get(route('groups.conversations.create', $group))
        ->assertForbidden();
});

test('leaders can access the create page when messaging is leaders only', function (): void {
    $leader = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ONLY_LEADERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);

    $this->actingAs($leader)
        ->get(route('groups.conversations.create', $group))
        ->assertSuccessful();
});

// --- Creating a conversation ---

test('a member can create a conversation and an opening comment', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $author);
    attachMember($group, $other);

    $this->actingAs($author);

    Livewire::test('pages::groups.conversations.create', ['group' => $group])
        ->set('title', 'Welcome aboard')
        ->set('body', '<p>Hi everyone!</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $conversation = Conversation::where('group_id', $group->id)->firstOrFail();

    expect($conversation->title)->toBe('Welcome aboard');
    expect($conversation->user_id)->toBe($author->id);
    expect($conversation->allow_replies)->toBeTrue();
    expect($conversation->pinned_at)->toBeNull();
    expect($conversation->comments()->count())->toBe(1);
    expect($conversation->refresh()->last_comment_at)->not->toBeNull();

    Notification::assertSentTo($other, NewConversationNotification::class);
    Notification::assertNotSentTo($author, NewConversationNotification::class);
});

test('a member can create a conversation with replies disabled', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $author);

    $this->actingAs($author);

    Livewire::test('pages::groups.conversations.create', ['group' => $group])
        ->set('title', 'Announcement only')
        ->set('body', '<p>Read-only.</p>')
        ->set('allowReplies', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $conversation = Conversation::where('group_id', $group->id)->firstOrFail();
    expect($conversation->allow_replies)->toBeFalse();
});

test('a leader can pin the conversation on create', function (): void {
    Notification::fake();

    $leader = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);

    $this->actingAs($leader);

    Livewire::test('pages::groups.conversations.create', ['group' => $group])
        ->set('title', 'Read me first')
        ->set('body', '<p>Body</p>')
        ->set('pinOnPost', true)
        ->call('save')
        ->assertHasNoErrors();

    $conversation = Conversation::where('group_id', $group->id)->firstOrFail();
    expect($conversation->pinned_at)->not->toBeNull();
    expect($conversation->pinned_by_user_id)->toBe($leader->id);
});

test('a non-leader pinOnPost flag is ignored', function (): void {
    Notification::fake();

    $member = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $member);

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.create', ['group' => $group])
        ->set('title', 'Trying to pin')
        ->set('body', '<p>Body</p>')
        ->set('pinOnPost', true)
        ->call('save')
        ->assertHasNoErrors();

    $conversation = Conversation::where('group_id', $group->id)->firstOrFail();
    expect($conversation->pinned_at)->toBeNull();
});

test('an empty body fails validation', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $author);

    $this->actingAs($author);

    Livewire::test('pages::groups.conversations.create', ['group' => $group])
        ->set('title', 'Empty')
        ->set('body', '<p>   </p>')
        ->call('save')
        ->assertHasErrors('body');
});

// --- Viewing a conversation ---

test('non-members cannot view a conversation in a public group', function (): void {
    $member = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $member->id,
    ]);

    $this->actingAs($other)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertForbidden();
});

test('members can view a conversation', function (): void {
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $member->id,
        'title' => 'Sunday plans',
    ]);
    $conversation->postComment('Opening message', $member);

    $this->actingAs($member)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSuccessful()
        ->assertSee('Sunday plans')
        ->assertSee('Opening message');
});

test('a 404 is returned when the conversation does not belong to the group', function (): void {
    $user = User::factory()->create();
    $groupA = Group::factory()->create(['messaging' => GroupMessaging::ALL_MEMBERS]);
    $groupB = Group::factory()->create(['messaging' => GroupMessaging::ALL_MEMBERS]);
    attachMember($groupA, $user);
    attachMember($groupB, $user);

    $conversation = Conversation::factory()->create([
        'group_id' => $groupA->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get("/groups/{$groupB->id}/conversations/{$conversation->id}")
        ->assertNotFound();
});

// --- Posting replies (policy::comment) ---

test('a member can post a reply', function (): void {
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $member->id,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>Reply text</p>')
        ->call('postReply')
        ->assertHasNoErrors();

    expect($conversation->comments()->count())->toBe(1);
    expect($conversation->refresh()->last_comment_at)->not->toBeNull();
});

test('an empty reply fails validation', function (): void {
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $member->id,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>   </p>')
        ->call('postReply')
        ->assertHasErrors('reply');

    expect($conversation->comments()->count())->toBe(0);
});

test('a non-leader cannot reply when replies are disabled on the conversation', function (): void {
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);
    attachMember($group, $member);

    $conversation = Conversation::factory()->repliesDisabled()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>Should fail</p>')
        ->call('postReply')
        ->assertForbidden();

    expect($conversation->comments()->count())->toBe(0);
});

test('a leader can reply when replies are disabled', function (): void {
    $leader = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);

    $conversation = Conversation::factory()->repliesDisabled()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>Leader can post</p>')
        ->call('postReply')
        ->assertHasNoErrors();

    expect($conversation->comments()->count())->toBe(1);
});

test('a leader can toggle replies on and off from the show page', function (): void {
    $leader = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('toggleReplies');

    expect($conversation->refresh()->allow_replies)->toBeFalse();

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('toggleReplies');

    expect($conversation->refresh()->allow_replies)->toBeTrue();
});

test('a non-leader cannot toggle replies', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $author);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($author);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('toggleReplies')
        ->assertForbidden();

    expect($conversation->refresh()->allow_replies)->toBeTrue();
});

test('a non-leader cannot reply when messaging is leaders only', function (): void {
    $member = User::factory()->create();
    $leader = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ONLY_LEADERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>Should fail</p>')
        ->call('postReply')
        ->assertForbidden();
});

// --- Reactions ---

test('a member can react to a conversation comment', function (): void {
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $member->id,
    ]);
    $conversation->postComment('Opening message', $member);
    $comment = $conversation->comments()->first();

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('react', $comment->id, '👍');

    expect($comment->fresh()->reactions()->count())->toBe(1);
});

test('a non-leader cannot react when messaging is leaders only', function (): void {
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ONLY_LEADERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);
    $conversation->postComment('Leader message', $leader);
    $comment = $conversation->comments()->first();

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('react', $comment->id, '👍')
        ->assertForbidden();

    expect($comment->fresh()->reactions()->count())->toBe(0);
});

test('reacting with a disallowed value is rejected', function (): void {
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $member->id,
    ]);
    $conversation->postComment('Opening message', $member);
    $comment = $conversation->comments()->first();

    $this->actingAs($member);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('react', $comment->id, '<script>alert(1)</script>')
        ->assertStatus(422);

    expect($comment->fresh()->reactions()->count())->toBe(0);
});

// --- Delete (policy::delete) ---

test('an author who is not a leader cannot delete their own conversation', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $author);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($author);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('deleteConversation')
        ->assertForbidden();

    $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
});

test('an author who is also a leader can delete their own conversation', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $author, GroupRole::LEADER);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($author);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('deleteConversation')
        ->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
});

test('a leader can delete any conversation in their group', function (): void {
    $leader = User::factory()->create();
    $author = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $leader, GroupRole::LEADER);
    attachMember($group, $author);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('deleteConversation')
        ->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
});

test('a non-author non-leader member cannot delete a conversation', function (): void {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create([
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    attachMember($group, $author);
    attachMember($group, $other);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($other);

    Livewire::test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('deleteConversation')
        ->assertForbidden();

    $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
});

// --- Cleanup cascade ---

test('deleting the group deletes its conversations', function (): void {
    $author = User::factory()->create();
    $group = Group::factory()->create();
    attachMember($group, $author);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    $group->delete();

    $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
});
