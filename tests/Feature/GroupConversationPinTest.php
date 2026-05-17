<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function pinTestGroup(): Group
{
    return Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
}

function attachPin(Group $group, User $user, GroupRole $role = GroupRole::MEMBER): void
{
    $group->allUsers()->attach($user, [
        'role' => $role,
        'status' => MembershipStatus::ACTIVE,
    ]);
}

test('a leader can pin a conversation', function (): void {
    $leader = User::factory()->create();
    $group = pinTestGroup();
    attachPin($group, $leader, GroupRole::LEADER);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    Livewire::actingAs($leader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('pinConversation', $conversation->id);

    expect($conversation->refresh()->pinned_at)->not->toBeNull();
    expect($conversation->pinned_by_user_id)->toBe($leader->id);
});

test('a leader can unpin a conversation', function (): void {
    $leader = User::factory()->create();
    $group = pinTestGroup();
    attachPin($group, $leader, GroupRole::LEADER);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);
    $conversation->pin($leader);

    Livewire::actingAs($leader)
        ->test('pages::groups.show', ['group' => $group])
        ->call('unpinConversation', $conversation->id);

    expect($conversation->refresh()->pinned_at)->toBeNull();
    expect($conversation->pinned_by_user_id)->toBeNull();
});

test('a regular member cannot pin a conversation', function (): void {
    $member = User::factory()->create();
    $leader = User::factory()->create();
    $group = pinTestGroup();
    attachPin($group, $leader, GroupRole::LEADER);
    attachPin($group, $member);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);

    Livewire::actingAs($member)
        ->test('pages::groups.show', ['group' => $group])
        ->call('pinConversation', $conversation->id)
        ->assertForbidden();

    expect($conversation->refresh()->pinned_at)->toBeNull();
});

test('pinned conversations sort before unpinned ones', function (): void {
    $leader = User::factory()->create();
    $group = pinTestGroup();
    attachPin($group, $leader, GroupRole::LEADER);

    $oldUnpinned = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
        'last_comment_at' => now()->subDay(),
    ]);
    $newUnpinned = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
        'last_comment_at' => now()->subHour(),
    ]);
    $pinned = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
        'last_comment_at' => now()->subWeek(),
    ]);
    $pinned->pin($leader);

    $component = Livewire::actingAs($leader)
        ->test('pages::groups.show', ['group' => $group]);

    $conversations = $component->invade()->conversations;

    expect($conversations->first()->id)->toBe($pinned->id);
    expect($conversations->skip(1)->first()->id)->toBe($newUnpinned->id);
    expect($conversations->last()->id)->toBe($oldUnpinned->id);
});

test('the conversations list shows a Pinned section when conversations are pinned', function (): void {
    $leader = User::factory()->create();
    $group = pinTestGroup();
    attachPin($group, $leader, GroupRole::LEADER);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);
    $conversation->pin($leader);

    $this->actingAs($leader)
        ->get("/groups/{$group->id}")
        ->assertSuccessful()
        ->assertSee('Pinned')
        ->assertSee($conversation->title);
});
