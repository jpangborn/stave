<?php

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use App\Models\Reading;
use App\Models\Service;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets guests view a service bulletin by id', function (): void {
    $service = Service::factory()->create([
        'title' => 'Sunday Morning Service',
        'date' => '2026-05-03',
    ]);

    $service->liturgyElements()->create([
        'type' => LiturgyElementType::PRAYER,
        'name' => 'Prayer of Confession',
        'order' => 1,
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee('Prayer of Confession')
        ->assertSee('Sun, May 3, 2026');
});

it('resolves the soonest upcoming service on /bulletin', function (): void {
    Service::factory()->create(['date' => now()->subWeek()])
        ->liturgyElements()->create(['type' => LiturgyElementType::SERMON, 'name' => 'Past Sermon', 'order' => 1]);

    Service::factory()->create(['date' => now()])
        ->liturgyElements()->create(['type' => LiturgyElementType::SERMON, 'name' => 'Todays Sermon', 'order' => 1]);

    Service::factory()->create(['date' => now()->addWeek()])
        ->liturgyElements()->create(['type' => LiturgyElementType::SERMON, 'name' => 'Future Sermon', 'order' => 1]);

    $this->get(route('bulletin.current'))
        ->assertSuccessful()
        ->assertSee('Todays Sermon')
        ->assertDontSee('Past Sermon')
        ->assertDontSee('Future Sermon');
});

it('breaks same-date ties by id when resolving the current service', function (): void {
    $first = Service::factory()->create(['date' => now()->addDay()]);
    Service::factory()->create(['date' => now()->addDay()]);

    expect(Service::current()->id)->toBe($first->id);
});

it('shows the empty state when only past services exist', function (): void {
    Service::factory()->create(['date' => now()->subWeek()])
        ->liturgyElements()->create(['type' => LiturgyElementType::SERMON, 'name' => 'Past Sermon', 'order' => 1]);

    $this->get(route('bulletin.current'))
        ->assertSuccessful()
        ->assertSee('No upcoming service is scheduled')
        ->assertDontSee('Past Sermon');
});

it('shows the empty state when no services exist', function (): void {
    $this->get(route('bulletin.current'))
        ->assertSuccessful()
        ->assertSee('No upcoming service is scheduled');
});

it('returns 404 for an unknown service id', function (): void {
    $this->get('/bulletin/999999')->assertNotFound();
});

it('shows song lyrics and CCLI details', function (): void {
    $song = Song::factory()->create([
        'name' => 'Doxology',
        'lyrics' => '<p>Praise God, from whom all blessings flow</p>',
        'authors' => 'Thomas Ken',
        'copyright' => 'Public Domain',
        'ccli_number' => '56204',
    ]);

    $service = Service::factory()->create(['date' => now()]);
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Doxology',
        'description' => null,
        'order' => 1,
        'content_type' => Song::class,
        'content_id' => $song->id,
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee('Praise God, from whom all blessings flow')
        ->assertSee('Thomas Ken')
        ->assertSee('Public Domain')
        ->assertSee('CCLI Song #56204');
});

it('shows reading text with the reading type as the kind label', function (): void {
    $reading = Reading::factory()->create([
        'title' => 'Psalm 147',
        'text' => '<p>Sing to the LORD with thanksgiving</p>',
    ]);

    $service = Service::factory()->create(['date' => now()]);
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::READING,
        'reading_type' => ReadingType::WORSHIP_CALL,
        'name' => 'Call to Worship',
        'order' => 1,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee('Sing to the LORD with thanksgiving')
        ->assertSee('Call to Worship');
});

it('hides assignees on non-sermon elements', function (): void {
    $user = User::factory()->create(['name' => 'Hidden Assignee Name']);

    $service = Service::factory()->create(['date' => now()]);
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::READING,
        'name' => 'Call to Worship',
        'order' => 1,
        'assignee_id' => $user->id,
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertDontSee('Hidden Assignee Name');
});

it('shows the preacher on the sermon element', function (): void {
    $user = User::factory()->create(['name' => 'Rev. Joshua Pangborn']);

    $service = Service::factory()->create(['date' => now()]);
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SERMON,
        'name' => 'The God Who Provides',
        'order' => 1,
        'assignee_id' => $user->id,
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee('Preaching')
        ->assertSee('Rev. Joshua Pangborn');
});

it('never renders internal service notes', function (): void {
    $service = Service::factory()->create([
        'date' => now(),
        'notes' => 'Internal planning notes for staff only',
    ]);
    $service->liturgyElements()->create(['type' => LiturgyElementType::SERMON, 'name' => 'Sermon', 'order' => 1]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertDontSee('Internal planning notes for staff only');
});

it('excludes sections from the element count', function (): void {
    $service = Service::factory()->create(['date' => now()]);

    $service->liturgyElements()->createMany([
        ['type' => LiturgyElementType::SECTION, 'name' => 'God', 'order' => 1],
        ['type' => LiturgyElementType::READING, 'name' => 'Call to Worship', 'order' => 2],
        ['type' => LiturgyElementType::SONG, 'name' => 'Doxology', 'order' => 3],
        ['type' => LiturgyElementType::SECTION, 'name' => 'Word', 'order' => 4],
        ['type' => LiturgyElementType::SERMON, 'name' => 'Sermon', 'order' => 5],
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee('of 3');
});

it('shows a no-content card for a contentless song', function (): void {
    $service = Service::factory()->create(['date' => now()]);
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Doxology',
        'description' => null,
        'order' => 1,
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee('No content was attached to this element yet.');
});

it('shows the entering card on the first element of a movement', function (): void {
    $service = Service::factory()->create(['date' => now()]);

    $service->liturgyElements()->createMany([
        ['type' => LiturgyElementType::SECTION, 'name' => 'God', 'description' => 'We start with a proclamation of God.', 'order' => 1],
        ['type' => LiturgyElementType::READING, 'name' => 'Call to Worship', 'order' => 2],
    ]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee('Entering · God')
        ->assertSee('We start with a proclamation of God.');
});

it('renders a service with no elements without a counter', function (): void {
    $service = Service::factory()->create(['date' => now()]);

    $this->get(route('bulletin.show', $service))
        ->assertSuccessful()
        ->assertSee("This service doesn't have any elements yet.", false);
});
