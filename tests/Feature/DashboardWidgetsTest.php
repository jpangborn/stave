<?php

use App\Enums\AccessRole;
use App\Enums\GroupMembershipStatus;
use App\Enums\LiturgyElementType;
use App\Models\Group;
use App\Models\LiturgyElement;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\Reading;
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

/* ----------------------------- display_title ----------------------------- */

test('display_title falls back from title to template name to a placeholder', function (?string $title, bool $withTemplate, string $expected): void {
    $template = $withTemplate ? Template::factory()->create(['name' => 'Sunday Morning']) : null;
    $service = Service::factory()->create(['title' => $title, 'template_id' => $template?->id]);

    expect($service->display_title)->toBe($expected);
})->with([
    'title set' => ['Easter Sunday', true, 'Easter Sunday'],
    'null title with template' => [null, true, 'Sunday Morning'],
    'null title without template' => [null, false, 'Untitled Service'],
]);

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

/* ------------------------------- readiness ------------------------------- */

test('readiness is null when there is no upcoming service', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);

    $readiness = Livewire::actingAs($admin)->test('pages::dashboard.index')->instance()->readiness;

    expect($readiness)->toBeNull();
});

test('readiness computes missing, unassigned, total, ready, and pct for a mixed service', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);

    $service = Service::factory()->create(['date' => today()]);

    // Excluded from all counts.
    LiturgyElement::factory()->section()->for($service, 'liturgy')->create();

    // Missing content (assigned, but requires content it doesn't have).
    LiturgyElement::factory()->withAssignee()->for($service, 'liturgy')->create([
        'type' => LiturgyElementType::SONG,
    ]);

    // Unassigned (doesn't require content, so it's not "missing").
    LiturgyElement::factory()->for($service, 'liturgy')->create([
        'type' => LiturgyElementType::PRAYER,
    ]);

    // Fully ready: has content and is assigned.
    $reading = Reading::factory()->create();
    LiturgyElement::factory()->withAssignee()->for($service, 'liturgy')->create([
        'type' => LiturgyElementType::READING,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
    ]);

    // Overlap: both missing content AND unassigned (e.g. a song freshly
    // created from a template). Naive `total - missing - unassigned` arithmetic
    // double-subtracts this element, undercounting ready.
    LiturgyElement::factory()->for($service, 'liturgy')->create([
        'type' => LiturgyElementType::SONG,
    ]);

    $readiness = Livewire::actingAs($admin)->test('pages::dashboard.index')->instance()->readiness;

    expect($readiness['service']->is($service))->toBeTrue()
        ->and($readiness['total'])->toBe(4)
        ->and($readiness['missing'])->toBe(2)
        ->and($readiness['unassigned'])->toBe(2)
        ->and($readiness['ready'])->toBe(1)
        ->and($readiness['pct'])->toBe(25);
});

test('readiness reports full pct when all elements are ready', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);

    $service = Service::factory()->create(['date' => today()]);

    LiturgyElement::factory()->withAssignee()->for($service, 'liturgy')->create([
        'type' => LiturgyElementType::PRAYER,
    ]);

    $readiness = Livewire::actingAs($admin)->test('pages::dashboard.index')->instance()->readiness;

    expect($readiness['missing'])->toBe(0)
        ->and($readiness['unassigned'])->toBe(0)
        ->and($readiness['pct'])->toBe(100);
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

test('liturgy admin sees Service Readiness heading on initial render', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);
    $this->actingAs($admin);

    $response = $this->get('/dashboard');

    $response->assertSee('Service Readiness');
});

test('liturgy user does not see Service Readiness on initial render', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::LITURGY_USER);
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertDontSee('Service Readiness');
});

/* ---------------------------- upcomingBirthdays ---------------------------- */

test('upcomingBirthdays orders cross-year birthdays correctly and excludes far-off or missing dates', function (): void {
    $this->travelTo(Carbon::parse('2026-12-20'));

    // Pin the acting user's own birth_date well outside the window: the User
    // factory's underlying Person otherwise gets a random birth_date that can
    // flakily fall inside the 30-day window.
    $user = User::factory()->create();
    $user->person->update(['birth_date' => '1990-06-01']);
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    $dec25 = Person::factory()->create(['birth_date' => '1990-12-25']);
    $jan5 = Person::factory()->create(['birth_date' => '1985-01-05']);
    $feb15 = Person::factory()->create(['birth_date' => '1990-02-15']);
    $noBirthDate = Person::factory()->create(['birth_date' => null]);

    $ids = Livewire::actingAs($user)->test('pages::dashboard.index')->instance()->upcomingBirthdays->pluck('id')->all();

    expect($ids)->toBe([$dec25->id, $jan5->id])
        ->and($ids)->not->toContain($feb15->id)
        ->and($ids)->not->toContain($noBirthDate->id);
});

test('upcomingBirthdays sorts a birthday today first', function (): void {
    $this->travelTo(Carbon::parse('2026-12-20'));

    $user = User::factory()->create();
    $user->person->update(['birth_date' => '1990-06-01']);
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    $dec25 = Person::factory()->create(['birth_date' => '1990-12-25']);
    $today = Person::factory()->create(['birth_date' => '1990-12-20']);

    $ids = Livewire::actingAs($user)->test('pages::dashboard.index')->instance()->upcomingBirthdays->pluck('id')->all();

    expect($ids)->toBe([$today->id, $dec25->id]);
});

test('nextBirthday rolls a Feb 29 birthday to Feb 28 in a non-leap year', function (): void {
    $this->travelTo(Carbon::parse('2026-02-01')); // 2026 is not a leap year

    $user = User::factory()->create();
    $leapDayPerson = Person::factory()->create(['birth_date' => '1992-02-29']);

    $component = Livewire::actingAs($user)->test('pages::dashboard.index');

    expect($component->instance()->nextBirthday($leapDayPerson)->toDateString())->toBe('2026-02-28');
});

test('nextBirthday keeps a Feb 29 birthday on Feb 29 in a leap year', function (): void {
    $this->travelTo(Carbon::parse('2028-01-01')); // 2028 is a leap year

    $user = User::factory()->create();
    $leapDayPerson = Person::factory()->create(['birth_date' => '1992-02-29']);

    $component = Livewire::actingAs($user)->test('pages::dashboard.index');

    expect($component->instance()->nextBirthday($leapDayPerson)->toDateString())->toBe('2028-02-29');
});

/* -------------------------- recentPrayerRequests -------------------------- */

test('recentPrayerRequests only includes open requests, newest first, limited to 5', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    PrayerRequest::factory()->completed()->create(['created_at' => now()]);

    $requests = collect(range(1, 6))->map(
        fn (int $daysAgo) => PrayerRequest::factory()->open()->create(['created_at' => now()->subDays($daysAgo)]),
    );
    $newest = $requests->first();
    $oldest = $requests->last();

    $result = Livewire::actingAs($user)->test('pages::dashboard.index')->instance()->recentPrayerRequests;

    expect($result)->toHaveCount(5)
        ->and($result->first()->id)->toBe($newest->id)
        ->and($result->pluck('id')->all())->not->toContain($oldest->id);
});

/* ------------------------------ pastoral initial render ------------------------------ */

test('pastoral user sees Prayer This Week and My Care List headings on initial render', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('Prayer This Week');
    $response->assertSee('My Care List');
});

test('plain user does not see pastoral islands on initial render', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertDontSee('Prayer This Week');
    $response->assertDontSee('My Care List');
});

/* ------------------------------ island loads ------------------------------ */

test('upcoming-services island renders a service title after loadIsland', function (): void {
    $user = User::factory()->create();

    Service::factory()->create(['date' => today()->addDay()]);
    $service = Service::factory()->create(['date' => today()->addWeek()]);

    Livewire::actingAs($user)->test('pages::dashboard.index')
        ->loadIsland('upcoming-services')
        ->assertIslandSee('upcoming-services', $service->display_title);
});

test('my-assignments island shows the element name and Needs content badge', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::LITURGY_USER);

    $service = Service::factory()->create(['date' => today()->addWeek()]);
    $element = LiturgyElement::factory()->assignedTo($user)->for($service, 'liturgy')->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Opening Hymn',
    ]);

    Livewire::actingAs($user)->test('pages::dashboard.index')
        ->loadIsland('my-assignments')
        ->assertIslandSee('my-assignments', $element->name)
        ->assertIslandSee('my-assignments', 'Needs content');
});

test('direct-messages island shows the caught-up empty state', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test('pages::dashboard.index')
        ->loadIsland('direct-messages')
        ->assertIslandSee('direct-messages', 'You&#039;re all caught up.');
});

test('my-care-list island shows the assigned congregant name', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    $congregant = Person::factory()->create(['pastoral_care_elder_id' => $user->person->id]);

    Livewire::actingAs($user)->test('pages::dashboard.index')
        ->loadIsland('my-care-list')
        ->assertIslandSee('my-care-list', $congregant->full_name);
});

test('group-messages island region includes wire:poll.60s once loaded', function (): void {
    $user = User::factory()->create();
    $group = groupWithConversation($user);

    Livewire::actingAs($user)->test('pages::dashboard.index')
        ->loadIsland('group-messages')
        ->assertIslandSee('group-messages', 'wire:poll.60s')
        ->assertIslandSee('group-messages', $group->name);
});
