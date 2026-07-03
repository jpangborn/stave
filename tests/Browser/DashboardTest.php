<?php

use App\Enums\AccessRole;
use App\Enums\GroupMembershipStatus;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\PrayerRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @group browser */
it('shows a full-access user hydrated dashboard widgets', function (): void {
    $user = User::factory()->create(['name' => 'Dana Fuller']);
    $user->grantAccessRole(AccessRole::ADMIN);

    Service::factory()->create([
        'title' => 'Sunrise Easter Service',
        'date' => today()->addWeek(),
    ]);

    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
        'name' => 'Worship Team',
    ]);
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => GroupMembershipStatus::ACTIVE]);

    Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'title' => 'Worship Team Planning',
    ]);

    PrayerRequest::factory()->open()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Good ')
        ->assertSee('Quick actions')
        ->assertSee('Sunrise Easter Service')
        ->assertSee('Worship Team')
        ->assertNoJavascriptErrors()
        ->assertNoSmoke();
});

/** @group browser */
it('shows a plain member no gated widgets', function (): void {
    $user = User::factory()->create(['name' => 'Plain Member']);

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Upcoming Services')
        ->assertSee('Quick actions')
        ->assertDontSee('My Assignments')
        ->assertDontSee('Service Readiness')
        ->assertDontSee('Prayer This Week')
        ->assertNoJavascriptErrors()
        ->assertNoSmoke();
});
