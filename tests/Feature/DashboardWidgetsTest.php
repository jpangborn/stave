<?php

use App\Enums\GroupMembershipStatus;
use App\Models\Group;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
