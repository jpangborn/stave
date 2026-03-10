<?php

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\User;
use App\Notifications\GroupJoinRequestNotification;
use App\Notifications\GroupMemberAddedNotification;
use App\Notifications\GroupMembershipResponseNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group groups */

// --- Show Page Access ---

test('guests are redirected from the group show page', function (): void {
    $group = Group::factory()->create();

    $this->get("/groups/{$group->id}")
        ->assertRedirect('/login');
});

test('authenticated users can view a public group show page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);

    $this->actingAs($user)
        ->get("/groups/{$group->id}")
        ->assertStatus(200);
});

test('non-members cannot view a private group show page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PRIVATE]);

    $this->actingAs($user)
        ->get("/groups/{$group->id}")
        ->assertForbidden();
});

test('active members can view a private group show page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PRIVATE]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user)
        ->get("/groups/{$group->id}")
        ->assertStatus(200);
});

// --- Join Flow ---

test('user can request to join a public group', function (): void {
    Notification::fake();

    $leader = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('join')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupRole::MEMBER->value,
        'status' => MembershipStatus::PENDING->value,
    ]);

    Notification::assertSentTo($leader, GroupJoinRequestNotification::class);
});

test('user cannot join a private group', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PRIVATE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->assertForbidden();
});

test('user cannot join if already pending', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('join')
        ->assertForbidden();
});

test('user cannot join if already active member', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('join')
        ->assertForbidden();
});

test('rejected user can re-request to join', function (): void {
    Notification::fake();

    $leader = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::REJECTED]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('join')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::PENDING->value,
    ]);
});

test('user can cancel a pending join request', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('cancelRequest');

    $this->assertDatabaseMissing('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);
});

// --- Leader Approval/Rejection ---

test('leader can approve a pending request', function (): void {
    Notification::fake();

    $leader = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('approveMember', $user->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::ACTIVE->value,
    ]);

    Notification::assertSentTo($user, GroupMembershipResponseNotification::class, function ($notification) {
        return $notification->approved === true;
    });
});

test('leader can reject a pending request', function (): void {
    Notification::fake();

    $leader = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('rejectMember', $user->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::REJECTED->value,
    ]);

    Notification::assertSentTo($user, GroupMembershipResponseNotification::class, function ($notification) {
        return $notification->approved === false;
    });
});

test('non-leader cannot approve a pending request', function (): void {
    $member = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    $this->actingAs($member);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('approveMember', $user->id)
        ->assertForbidden();
});

// --- Leader Direct Add ---

test('leader can directly add a member', function (): void {
    Notification::fake();

    $leader = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->set('selectedUserId', $user->id)
        ->call('addMember')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupRole::MEMBER->value,
        'status' => MembershipStatus::ACTIVE->value,
    ]);

    Notification::assertSentTo($user, GroupMemberAddedNotification::class);
});

test('non-leader cannot add a member', function (): void {
    $member = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($member);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->set('selectedUserId', $user->id)
        ->call('addMember')
        ->assertForbidden();
});

// --- Leave Group ---

test('active member can leave a group', function (): void {
    $user = User::factory()->create();
    $leader = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('leave')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);
});

test('sole leader cannot leave the group', function (): void {
    $leader = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('leave')
        ->assertForbidden();
});

// --- Remove Member ---

test('leader can remove a member', function (): void {
    $leader = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('removeMember', $user->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);
});

test('cannot remove the sole leader', function (): void {
    $leader = User::factory()->create();
    $otherLeader = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->call('removeMember', $leader->id);

    // Leader should still be in the group
    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);
});

// --- Members Tab Visibility ---

test('members tab is visible to leaders', function (): void {
    $leader = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($leader);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->assertSee('Members');
});

test('members tab is not visible to regular members', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.show', ['group' => $group])
        ->assertDontSee('Add Member');
});

// --- My Groups on Index ---

test('my groups section shows active groups', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['name' => 'My Active Group', 'visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $pendingGroup = Group::factory()->create(['name' => 'Pending Group', 'visibility' => GroupVisibility::PRIVATE]);
    $pendingGroup->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    $this->actingAs($user);

    Livewire::test('pages::groups.index')
        ->assertSee('My Active Group')
        ->assertDontSee('Pending Group');
});
