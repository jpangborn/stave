<?php

use App\Enums\GroupMembershipStatus;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function roleGroup(): Group
{
    return Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
}

function attachRole(Group $group, User $user, GroupRole $role = GroupRole::MEMBER): void
{
    $group->allUsers()->attach($user, [
        'role' => $role,
        'status' => GroupMembershipStatus::ACTIVE,
    ]);
}

test('a leader can promote a member to leader', function (): void {
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $group = roleGroup();
    attachRole($group, $leader, GroupRole::LEADER);
    attachRole($group, $member);

    Livewire::actingAs($leader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('setMemberRole', $member->id, 'leader');

    $pivot = $group->allUsers()->whereKey($member->id)->first()->pivot;
    expect($pivot->role)->toBe(GroupRole::LEADER);
});

test('a leader can demote another leader to member', function (): void {
    $leaderA = User::factory()->create();
    $leaderB = User::factory()->create();
    $group = roleGroup();
    attachRole($group, $leaderA, GroupRole::LEADER);
    attachRole($group, $leaderB, GroupRole::LEADER);

    Livewire::actingAs($leaderA)
        ->test('pages::groups.show', ['group' => $group])
        ->call('setMemberRole', $leaderB->id, 'member');

    $pivot = $group->allUsers()->whereKey($leaderB->id)->first()->pivot;
    expect($pivot->role)->toBe(GroupRole::MEMBER);
});

test('the last leader cannot be demoted', function (): void {
    $onlyLeader = User::factory()->create();
    $member = User::factory()->create();
    $group = roleGroup();
    attachRole($group, $onlyLeader, GroupRole::LEADER);
    attachRole($group, $member);

    Livewire::actingAs($onlyLeader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('setMemberRole', $onlyLeader->id, 'member');

    $pivot = $group->allUsers()->whereKey($onlyLeader->id)->first()->pivot;
    expect($pivot->role)->toBe(GroupRole::LEADER);
});

test('the last leader cannot be removed from the group', function (): void {
    $onlyLeader = User::factory()->create();
    $member = User::factory()->create();
    $group = roleGroup();
    attachRole($group, $onlyLeader, GroupRole::LEADER);
    attachRole($group, $member);

    Livewire::actingAs($onlyLeader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('removeMember', $onlyLeader->id);

    expect($group->allUsers()->whereKey($onlyLeader->id)->exists())->toBeTrue();
});

test('a regular member cannot change roles', function (): void {
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $other = User::factory()->create();
    $group = roleGroup();
    attachRole($group, $leader, GroupRole::LEADER);
    attachRole($group, $member);
    attachRole($group, $other);

    Livewire::actingAs($member)
        ->test('pages::groups.show', ['group' => $group])
        ->call('setMemberRole', $other->id, 'leader')
        ->assertForbidden();

    $pivot = $group->allUsers()->whereKey($other->id)->first()->pivot;
    expect($pivot->role)->toBe(GroupRole::MEMBER);
});

test('setting a role to the same value is a no-op', function (): void {
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $group = roleGroup();
    attachRole($group, $leader, GroupRole::LEADER);
    attachRole($group, $member);

    Livewire::actingAs($leader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('setMemberRole', $member->id, 'member');

    $pivot = $group->allUsers()->whereKey($member->id)->first()->pivot;
    expect($pivot->role)->toBe(GroupRole::MEMBER);
});
