<?php

use App\Enums\LiturgyElementType;
use App\Models\Service;
use App\Models\Song;
use App\Models\User;
use App\Support\SectionTone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('seeds a tonal color when a section is created', function (): void {
    $service = Service::factory()->create();

    $section = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SECTION,
        'name' => 'Grace',
        'order' => 0,
    ]);

    expect($section->section_color)->toBe(SectionTone::pick('Grace'));
});

it('does not change section color when renamed', function (): void {
    $service = Service::factory()->create();
    $section = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SECTION,
        'name' => 'Grace',
        'order' => 0,
    ]);
    $originalColor = $section->section_color;

    $section->update(['name' => 'Mercy']);

    expect($section->fresh()->section_color)->toBe($originalColor);
});

it('exposes service stats counters', function (): void {
    $service = Service::factory()->create();
    $user = User::factory()->create();
    $song = Song::factory()->create();

    $service->liturgyElements()->createMany([
        ['type' => LiturgyElementType::SECTION, 'name' => 'God', 'order' => 0],
        ['type' => LiturgyElementType::SONG, 'name' => 'Doxology', 'assignee_id' => $user->id, 'content_type' => Song::class, 'content_id' => $song->id, 'order' => 1],
        ['type' => LiturgyElementType::SONG, 'name' => 'Holy Holy Holy', 'order' => 2],
        ['type' => LiturgyElementType::SECTION, 'name' => 'Grace', 'order' => 3],
        ['type' => LiturgyElementType::READING, 'name' => 'Scripture', 'order' => 4],
        ['type' => LiturgyElementType::SERMON, 'name' => 'Sermon', 'order' => 5],
        ['type' => LiturgyElementType::PRAYER, 'name' => 'Closing prayer', 'order' => 6],
    ]);
    $service->refresh()->load('liturgyElements');

    expect($service->sectionCount())->toBe(2);
    expect($service->elementCount())->toBe(5);
    // Four non-section elements have no assignee: the second song, reading, sermon, prayer
    expect($service->unassignedCount())->toBe(4);
    // Missing-content excludes sermon/prayer; song without content + reading without content = 2
    expect($service->missingContentCount())->toBe(2);
});

it('renders the new header with date block, title and template chip', function (): void {
    $service = Service::factory()->create([
        'title' => 'Sunday Morning Service',
        'date' => '2026-05-17',
    ]);

    $this->get(route('services.show', $service))
        ->assertOk()
        ->assertSee('Sunday Morning Service')
        ->assertSeeText('May')
        ->assertSeeText('17')
        ->assertSeeText('2026')
        ->assertSee('Duplicate')
        ->assertSee('View Bulletin');
});

it('updates the service title inline', function (): void {
    $service = Service::factory()->create(['title' => 'Old Title']);

    Livewire::test('pages::services.show', ['service' => $service])
        ->set('form.title', 'New Title');

    expect($service->fresh()->title)->toBe('New Title');
});

it('duplicates a service and its liturgy elements', function (): void {
    $service = Service::factory()->create(['title' => 'Original']);
    $service->liturgyElements()->createMany([
        ['type' => LiturgyElementType::SECTION, 'name' => 'God', 'order' => 0],
        ['type' => LiturgyElementType::SONG, 'name' => 'Doxology', 'order' => 1],
    ]);

    Livewire::test('pages::services.show', ['service' => $service])
        ->call('duplicate')
        ->assertRedirect();

    $clone = Service::where('title', 'Original (Copy)')->first();

    expect($clone)->not->toBeNull();
    expect($clone->id)->not->toBe($service->id);
    expect($clone->liturgyElements)->toHaveCount(2);
    expect($clone->liturgyElements->first()->name)->toBe('God');
});

it('switches to the bulletin tab when View Bulletin is clicked', function (): void {
    $service = Service::factory()->create();

    Livewire::test('pages::services.show', ['service' => $service])
        ->call('viewBulletin')
        ->assertSet('tab', 'bulletin');
});

it('inserts a new element after the anchor', function (): void {
    $service = Service::factory()->create();
    $section = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SECTION,
        'name' => 'God',
        'order' => 0,
    ]);
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Doxology',
        'order' => 1,
    ]);

    Livewire::test('services.elements', ['serviceId' => $service->id])
        ->call('addElementAfter', $section->id, LiturgyElementType::READING->value);

    $service->refresh()->load('liturgyElements');
    $elements = $service->liturgyElements;

    expect($elements)->toHaveCount(3);
    expect($elements[0]->id)->toBe($section->id);
    expect($elements[1]->type)->toBe(LiturgyElementType::READING);
    expect($elements[2]->id)->toBe($song->id);
});

it('saves a new assignee through the chip popover', function (): void {
    $service = Service::factory()->create();
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SECTION,
        'name' => 'God',
        'order' => 0,
    ]);
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Doxology',
        'order' => 1,
    ]);
    $user = User::factory()->create();

    Livewire::test('elements.song', ['element' => $song])
        ->call('setAssignee', $user->id);

    expect($song->fresh()->assignee_id)->toBe($user->id);
});

it('clears an assignee when null is passed', function (): void {
    $service = Service::factory()->create();
    $user = User::factory()->create();
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Doxology',
        'assignee_id' => $user->id,
        'order' => 0,
    ]);

    Livewire::test('elements.song', ['element' => $song])
        ->call('setAssignee', null);

    expect($song->fresh()->assignee_id)->toBeNull();
});

it('saves a new song selection through the content chip', function (): void {
    $service = Service::factory()->create();
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Pick one',
        'order' => 0,
    ]);
    $library = Song::factory()->create(['name' => 'Doxology']);

    Livewire::test('elements.song', ['element' => $song])
        ->call('setContent', $library->id);

    $song->refresh();
    expect($song->content_id)->toBe($library->id);
    expect($song->content_type)->toBe(Song::class);
});

it('clears a song selection when null is passed', function (): void {
    $service = Service::factory()->create();
    $library = Song::factory()->create();
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Doxology',
        'content_type' => Song::class,
        'content_id' => $library->id,
        'order' => 0,
    ]);

    Livewire::test('elements.song', ['element' => $song])
        ->call('setContent', null);

    $song->refresh();
    expect($song->content_id)->toBeNull();
    expect($song->content_type)->toBeNull();
});

it('recolors a section', function (): void {
    $service = Service::factory()->create();
    $section = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SECTION,
        'name' => 'God',
        'order' => 0,
    ]);

    Livewire::test('elements.section', ['element' => $section])
        ->call('recolor', 'blue');

    expect($section->fresh()->section_color)->toBe('blue');
});

it('renames a section inline', function (): void {
    $service = Service::factory()->create();
    $section = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SECTION,
        'name' => 'God',
        'order' => 0,
    ]);

    Livewire::test('elements.section', ['element' => $section])
        ->set('name', 'God the Father');

    expect($section->fresh()->name)->toBe('God the Father');
});

it('renders an uppercase time-of-day descriptor in the header eyebrow', function (): void {
    $morning = Service::factory()->create([
        'title' => 'Morning Service',
        'date' => '2026-05-17 09:00:00',
    ]);

    $this->get(route('services.show', $morning))
        ->assertOk()
        ->assertSee('SUNDAY MORNING · MAY 17, 2026');

    $evening = Service::factory()->create([
        'title' => 'Evening Service',
        'date' => '2026-05-17 18:00:00',
    ]);

    $this->get(route('services.show', $evening))
        ->assertOk()
        ->assertSee('SUNDAY EVENING · MAY 17, 2026');
});

it('defaults the content preview to the current selection when opened', function (): void {
    $service = Service::factory()->create();
    $library = Song::factory()->create(['name' => 'Doxology']);
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Opening',
        'content_type' => Song::class,
        'content_id' => $library->id,
        'order' => 0,
    ]);

    Livewire::test('elements.song', ['element' => $song])
        ->call('openContent')
        ->assertSet('contentOpen', true)
        ->assertSet('hoverContentId', $library->id);
});

it('defaults the content preview to the first song when nothing is selected', function (): void {
    $service = Service::factory()->create();
    Song::factory()->count(3)->create();
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Opening',
        'order' => 0,
    ]);

    $component = Livewire::test('elements.song', ['element' => $song])
        ->call('openContent');

    expect($component->get('hoverContentId'))->not->toBeNull();
});

it('resolves a preview song from hoverContentId', function (): void {
    $service = Service::factory()->create();
    $library = Song::factory()->create(['name' => 'His Mercy Is More']);
    $song = $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'name' => 'Opening',
        'order' => 0,
    ]);

    $component = Livewire::test('elements.song', ['element' => $song])
        ->set('hoverContentId', $library->id);

    expect($component->get('previewSong')->id)->toBe($library->id);
});

it('renders the full service page with every element row type', function (): void {
    $service = Service::factory()->create(['title' => 'All-Type Service']);
    $service->liturgyElements()->createMany([
        ['type' => LiturgyElementType::SECTION, 'name' => 'Worship', 'order' => 0],
        ['type' => LiturgyElementType::SONG, 'name' => 'Doxology', 'order' => 1],
        ['type' => LiturgyElementType::READING, 'name' => 'Psalm 23', 'order' => 2],
        ['type' => LiturgyElementType::SERMON, 'name' => 'Sermon', 'description' => 'Of Christ', 'order' => 3],
        ['type' => LiturgyElementType::PRAYER, 'name' => 'Pastoral Prayer', 'order' => 4],
        ['type' => LiturgyElementType::SUPPER, 'name' => 'Communion', 'order' => 5],
        ['type' => LiturgyElementType::BAPTISM, 'name' => 'Baptism', 'order' => 6],
        ['type' => LiturgyElementType::OTHER, 'name' => 'Announcement', 'order' => 7],
    ]);

    $this->get(route('services.show', $service))
        ->assertOk()
        ->assertSee('All-Type Service')
        ->assertSee('Worship')
        ->assertSee('Doxology')
        ->assertSee('Psalm 23')
        ->assertSee('Sermon')
        ->assertSee('Pastoral Prayer')
        ->assertSee('Communion');
});
