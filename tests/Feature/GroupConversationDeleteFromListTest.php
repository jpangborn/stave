<?php

use App\Enums\GroupMembershipStatus;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function deleteListGroup(): Group
{
    return Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
}

function attachDel(Group $group, User $user, GroupRole $role = GroupRole::MEMBER): void
{
    $group->allUsers()->attach($user, [
        'role' => $role,
        'status' => GroupMembershipStatus::ACTIVE,
    ]);
}

test('a leader can delete a conversation from the list', function (): void {
    $leader = User::factory()->create();
    $group = deleteListGroup();
    attachDel($group, $leader, GroupRole::LEADER);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    Livewire::actingAs($leader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('openDeleteConversation', $conversation->id)
        ->assertSet('confirmDeleteId', $conversation->id)
        ->call('deleteConversation');

    expect(Conversation::find($conversation->id))->toBeNull();
});

test('a regular member cannot delete a conversation from the list', function (): void {
    $member = User::factory()->create();
    $leader = User::factory()->create();
    $group = deleteListGroup();
    attachDel($group, $leader, GroupRole::LEADER);
    attachDel($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    Livewire::actingAs($member)
        ->test('pages::groups.show', ['group' => $group])
        ->set('confirmDeleteId', $conversation->id)
        ->call('deleteConversation')
        ->assertForbidden();

    expect(Conversation::find($conversation->id))->not->toBeNull();
});

test('deleting clears the confirm id and closes the modal', function (): void {
    $leader = User::factory()->create();
    $group = deleteListGroup();
    attachDel($group, $leader, GroupRole::LEADER);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    Livewire::actingAs($leader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('openDeleteConversation', $conversation->id)
        ->call('deleteConversation')
        ->assertSet('confirmDeleteId', null);
});

test('delete confirmation modal is not triggered for non-leaders viewing the page', function (): void {
    $member = User::factory()->create();
    $leader = User::factory()->create();
    $group = deleteListGroup();
    attachDel($group, $leader, GroupRole::LEADER);
    attachDel($group, $member);

    Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
        'title' => 'A normal thread',
    ]);

    $this->actingAs($member)
        ->get("/groups/{$group->id}")
        ->assertSuccessful()
        ->assertSee('A normal thread')
        ->assertDontSee('Delete conversation');
});
