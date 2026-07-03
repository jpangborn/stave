<?php

use App\Enums\AccessRole;
use App\Enums\GroupMembershipStatus;
use App\Models\Group;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/* ------------------------- upcomingAssignments ------------------------- */

test('upcomingAssignments includes elements assigned to me in future and today\'s services', function (): void {
    $me = User::factory()->create();

    $todayService = Service::factory()->create(['date' => today()]);
    $futureService = Service::factory()->create(['date' => today()->addWeek()]);

    $todayElement = LiturgyElement::factory()->assignedTo($me)->for($todayService, 'liturgy')->create();
    $futureElement = LiturgyElement::factory()->assignedTo($me)->for($futureService, 'liturgy')->create();

    $ids = $me->upcomingAssignments()->pluck('id');

    expect($ids)->toContain($todayElement->id)
        ->toContain($futureElement->id)
        ->toHaveCount(2);
});

test('upcomingAssignments excludes another user\'s elements', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $service = Service::factory()->create(['date' => today()->addWeek()]);
    LiturgyElement::factory()->assignedTo($other)->for($service, 'liturgy')->create();

    expect($me->upcomingAssignments())->toBeEmpty();
});

test('upcomingAssignments excludes my elements in past services', function (): void {
    $me = User::factory()->create();

    $pastService = Service::factory()->create(['date' => today()->subWeek()]);
    LiturgyElement::factory()->assignedTo($me)->for($pastService, 'liturgy')->create();

    expect($me->upcomingAssignments())->toBeEmpty();
});

test('upcomingAssignments excludes my elements attached to a template', function (): void {
    $me = User::factory()->create();

    $template = Template::factory()->create();
    LiturgyElement::factory()->assignedTo($me)->for($template, 'liturgy')->create();

    expect($me->upcomingAssignments())->toBeEmpty();
});

test('upcomingAssignments orders results by the parent service date ascending', function (): void {
    $me = User::factory()->create();

    $laterService = Service::factory()->create(['date' => today()->addMonth()]);
    $soonerService = Service::factory()->create(['date' => today()->addDay()]);

    $laterElement = LiturgyElement::factory()->assignedTo($me)->for($laterService, 'liturgy')->create();
    $soonerElement = LiturgyElement::factory()->assignedTo($me)->for($soonerService, 'liturgy')->create();

    expect($me->upcomingAssignments()->pluck('id')->all())
        ->toBe([$soonerElement->id, $laterElement->id]);
});

test('upcomingAssignments eager-loads liturgy and content', function (): void {
    $me = User::factory()->create();

    $service = Service::factory()->create(['date' => today()->addWeek()]);
    LiturgyElement::factory()->assignedTo($me)->for($service, 'liturgy')->create();

    $assignments = $me->upcomingAssignments();

    expect($assignments->first()->relationLoaded('liturgy'))->toBeTrue()
        ->and($assignments->first()->relationLoaded('content'))->toBeTrue();
});

/* -------------------------- unreadGroupCounts -------------------------- */

/**
 * Build an active, non-direct group with the given member, and a
 * conversation containing comments authored by others.
 */
function groupWithConversation(User $member): Group
{
    $group = Group::factory()->create(['is_direct' => false]);
    $group->allUsers()->attach($member, [
        'role' => 'member',
        'status' => GroupMembershipStatus::ACTIVE,
    ]);

    return $group;
}

test('unreadGroupCounts counts others\' comments made after my last_read_at', function (): void {
    $me = User::factory()->create();
    $author = User::factory()->create();

    $group = groupWithConversation($me);
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Thread',
        'allow_replies' => true,
    ]);
    $conversation->markReadFor($me);

    $this->travelTo(now()->addMinute());
    $conversation->postComment('<p>new message</p>', $author);

    expect($me->unreadGroupCounts()->get($group->id))->toBe(1);
});

test('unreadGroupCounts excludes my own comments', function (): void {
    $me = User::factory()->create();

    $group = groupWithConversation($me);
    $conversation = $group->conversations()->create([
        'user_id' => $me->id,
        'title' => 'Thread',
        'allow_replies' => true,
    ]);
    $conversation->markReadFor($me);

    $this->travelTo(now()->addMinute());
    $conversation->postComment('<p>mine</p>', $me);

    expect($me->unreadGroupCounts()->get($group->id, 0))->toBe(0);
});

test('unreadGroupCounts matches Conversation::unreadCountFor when the pivot row is missing', function (): void {
    $me = User::factory()->create();
    $author = User::factory()->create();

    $group = groupWithConversation($me);
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Thread',
        'allow_replies' => true,
    ]);
    // No markReadFor call: the conversation_user pivot row never gets created.
    $conversation->postComment('<p>first</p>', $author);
    $conversation->postComment('<p>second</p>', $author);

    expect($me->unreadGroupCounts()->get($group->id))
        ->toBe($conversation->unreadCountFor($me));
});

test('unreadGroupCounts does not count comments made before my last_read_at', function (): void {
    $me = User::factory()->create();
    $author = User::factory()->create();

    $group = groupWithConversation($me);
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Thread',
        'allow_replies' => true,
    ]);
    $conversation->postComment('<p>old message</p>', $author);
    $conversation->markReadFor($me);

    expect($me->unreadGroupCounts()->get($group->id, 0))->toBe(0);
});

test('unreadGroupCounts excludes direct-group conversations', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;
    $conversation->postComment('<p>hi</p>', $other);

    expect($me->unreadGroupCounts())->toBeEmpty();
});

test('unreadGroupCounts omits groups with zero unread', function (): void {
    $me = User::factory()->create();
    $author = User::factory()->create();

    $group = groupWithConversation($me);
    $conversation = $group->conversations()->create([
        'user_id' => $author->id,
        'title' => 'Thread',
        'allow_replies' => true,
    ]);
    $conversation->postComment('<p>old message</p>', $author);
    $conversation->markReadFor($me);

    expect($me->unreadGroupCounts()->has($group->id))->toBeFalse();
});

/* -------------------------- unreadDirectCounts -------------------------- */

test('unreadDirectCounts counts others\' comments made after my last_read_at', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;
    $conversation->markReadFor($me);

    $this->travelTo(now()->addMinute());
    $conversation->postComment('<p>new message</p>', $other);

    expect($me->unreadDirectCounts()->get($conversation->id))->toBe(1);
});

test('unreadDirectCounts excludes my own comments', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;
    $conversation->markReadFor($me);

    $this->travelTo(now()->addMinute());
    $conversation->postComment('<p>mine</p>', $me);

    expect($me->unreadDirectCounts()->get($conversation->id, 0))->toBe(0);
});

test('unreadDirectCounts matches Conversation::unreadCountFor when the pivot row is missing', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;
    // No markReadFor call: the conversation_user pivot row never gets created.
    $conversation->postComment('<p>first</p>', $other);
    $conversation->postComment('<p>second</p>', $other);

    expect($me->unreadDirectCounts()->get($conversation->id))
        ->toBe($conversation->unreadCountFor($me));
});

test('unreadDirectCounts does not count comments made before my last_read_at', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;
    $conversation->postComment('<p>old message</p>', $other);
    $conversation->markReadFor($me);

    expect($me->unreadDirectCounts()->get($conversation->id, 0))->toBe(0);
});

test('unreadDirectCounts excludes non-direct group conversations', function (): void {
    $me = User::factory()->create();

    $group = groupWithConversation($me);
    $conversation = $group->conversations()->create([
        'user_id' => $me->id,
        'title' => 'Thread',
        'allow_replies' => true,
    ]);
    $author = User::factory()->create();
    $group->allUsers()->attach($author, [
        'role' => 'member',
        'status' => GroupMembershipStatus::ACTIVE,
    ]);
    $conversation->postComment('<p>hi</p>', $author);

    expect($me->unreadDirectCounts())->toBeEmpty();
});

test('unreadDirectCounts is keyed by conversation id', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;
    $conversation->postComment('<p>hi</p>', $other);

    expect($me->unreadDirectCounts()->keys()->all())->toBe([$conversation->id]);
});

/* --------------------------- upcomingServices --------------------------- */

test('upcomingServices returns at most 3 services, ascending by date, excluding past services', function (): void {
    $user = User::factory()->create();

    Service::factory()->create(['date' => today()->subWeek()]);
    $first = Service::factory()->create(['date' => today()]);
    $second = Service::factory()->create(['date' => today()->addDay()]);
    $third = Service::factory()->create(['date' => today()->addWeek()]);
    Service::factory()->create(['date' => today()->addMonth()]);

    $upcoming = Livewire::actingAs($user)->test('pages::dashboard.index')->instance()->upcomingServices;

    expect($upcoming->pluck('id')->all())->toBe([$first->id, $second->id, $third->id]);
});

/* --------------------------- relativeDateBadge --------------------------- */

test('relativeDateBadge labels dates relative to today', function (): void {
    $this->travelTo(Carbon::parse('2026-07-03')); // Friday

    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('pages::dashboard.index');

    expect($component->instance()->relativeDateBadge(today()))->toBe('Today')
        ->and($component->instance()->relativeDateBadge(today()->addDay()))->toBe('Tomorrow')
        ->and($component->instance()->relativeDateBadge(today()->next(Carbon::SUNDAY)))->toBe('This Sunday')
        ->and($component->instance()->relativeDateBadge(today()->addDays(10)))->toBe('In 10 days');
});

/* ------------------------------ initial render ------------------------------ */

test('liturgy user sees My Assignments heading on initial render', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::LITURGY_USER);
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('My Assignments');
});

test('plain user does not see My Assignments but sees the other islands on initial render', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertDontSee('My Assignments');
    $response->assertSee('Upcoming Services');
    $response->assertSee('Group Messages');
    $response->assertSee('Messages');
});
