<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @group browser */
it('renders an empty group conversation without smoke', function (): void {
    $user = User::factory()->create(['name' => 'Smoke Tester']);
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
        'name' => 'Elders',
    ]);
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'title' => 'Smoke conversation',
    ]);

    $this->actingAs($user);

    $page = visit(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]));

    $page->assertSee('Smoke conversation')
        ->assertSee('No messages yet')
        ->assertNoJavascriptErrors()
        ->assertNoSmoke();
});

/** @group browser */
it('renders a populated group conversation with pinned, prayer, and scripture refs', function (): void {
    $leader = User::factory()->create(['name' => 'Karen Reyes']);
    $member = User::factory()->create(['name' => 'Marcus Tan']);
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
        'name' => 'Elders',
    ]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
        'title' => 'Sunday Service Planning',
    ]);

    $pinned = $conversation->postComment('<p>Anchor the set in Romans 8:31 tonight.</p>', $leader);
    $pinned->fresh()->pin($leader);

    $prayer = $conversation->postComment('<p>Praying for the Hawkins family.</p>', $member);
    $prayer->fresh()->togglePrayer();

    $conversation->postComment('<p>I will bring John 1:14 into the closing.</p>', $leader);

    $this->actingAs($leader);

    $page = visit(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]));

    $page->assertSee('Sunday Service Planning')
        ->assertSee('Pinned by Karen Reyes')
        ->assertSee('Romans 8:31')
        ->assertSee('John 1:14')
        ->assertSee('Prayer')
        ->assertNoJavascriptErrors()
        ->assertNoSmoke();
});
