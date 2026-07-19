<?php

use App\Enums\LiturgyElementType;
use App\Models\Church;
use App\Models\Group;
use App\Models\Person;
use App\Models\Service;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('index pages only show records from the current church', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    Song::factory()->create(['church_id' => $a->id, 'name' => 'Alpha Anthem']);
    Song::factory()->create(['church_id' => $b->id, 'name' => 'Beta Ballad']);
    Service::factory()->create(['church_id' => $a->id, 'title' => 'Alpha Service', 'date' => now()->addDay()]);
    Service::factory()->create(['church_id' => $b->id, 'title' => 'Beta Service', 'date' => now()->addDay()]);
    Person::factory()->create(['church_id' => $a->id, 'first_name' => 'Alpha', 'last_name' => 'Member']);
    Person::factory()->create(['church_id' => $b->id, 'first_name' => 'Beta', 'last_name' => 'Stranger']);
    Group::factory()->public()->create(['church_id' => $a->id, 'name' => 'Alpha Fellowship']);
    Group::factory()->public()->create(['church_id' => $b->id, 'name' => 'Beta Fellowship']);

    $user = User::factory()->forChurch($a)->create();

    $this->actingAs($user)->get('/songs')->assertOk()
        ->assertSee('Alpha Anthem')->assertDontSee('Beta Ballad');

    $this->actingAs($user)->get('/services')->assertOk()
        ->assertSee('Alpha Service')->assertDontSee('Beta Service');

    $this->actingAs($user)->get('/people')->assertOk()
        ->assertSee('Alpha')->assertDontSee('Stranger');

    $this->actingAs($user)->get('/groups')->assertOk()
        ->assertSee('Alpha Fellowship')->assertDontSee('Beta Fellowship');
});

test('route model binding 404s for records of another church', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $foreignSong = Song::factory()->create(['church_id' => $b->id]);
    $foreignService = Service::factory()->create(['church_id' => $b->id]);

    $user = User::factory()->forChurch($a)->create();

    $this->actingAs($user)->get("/songs/{$foreignSong->id}")->assertNotFound();
    $this->actingAs($user)->get("/songs/{$foreignSong->id}/edit")->assertNotFound();
    $this->actingAs($user)->get("/services/{$foreignService->id}")->assertNotFound();
});

test('factory-created records default to a single shared church', function (): void {
    $song = Song::factory()->create();
    $service = Service::factory()->create();
    $user = User::factory()->create();

    expect($song->church_id)->not->toBeNull()
        ->and($service->church_id)->toBe($song->church_id)
        ->and($user->current_church_id)->toBe($song->church_id);
});

test('last used date ignores usage recorded in another church', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $song = Song::factory()->create(['church_id' => $a->id, 'name' => 'Shared Name']);

    // Church B (incorrectly or coincidentally) references church A's song id.
    $foreignService = Service::factory()->create(['church_id' => $b->id, 'date' => now()->subWeek()]);
    $foreignService->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $song->id,
        'order' => 1,
        'name' => 'Song',
        'church_id' => $b->id,
    ]);

    $user = User::factory()->forChurch($a)->create();
    $this->actingAs($user);

    expect(Song::withLastUsedDate()->find($song->id)->last_used_date)->toBeNull();
});

test('upcoming assignments only include the current church', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $user = User::factory()->forChurch($a)->create();
    $user->churches()->attach($b);

    $serviceA = Service::factory()->create(['church_id' => $a->id, 'date' => now()->addDays(2)]);
    $serviceA->liturgyElements()->create([
        'type' => LiturgyElementType::READING,
        'order' => 1,
        'name' => 'Reading A',
        'assignee_id' => $user->id,
        'church_id' => $a->id,
    ]);

    $serviceB = Service::factory()->create(['church_id' => $b->id, 'date' => now()->addDays(3)]);
    $serviceB->liturgyElements()->create([
        'type' => LiturgyElementType::READING,
        'order' => 1,
        'name' => 'Reading B',
        'assignee_id' => $user->id,
        'church_id' => $b->id,
    ]);

    $this->actingAs($user);

    expect($user->upcomingAssignments()->pluck('name')->all())->toBe(['Reading A']);

    $user->switchChurch($b);
    $this->actingAs($user->fresh());

    expect($user->fresh()->upcomingAssignments()->pluck('name')->all())->toBe(['Reading B']);
});

test('creating records stamps the current church automatically', function (): void {
    $a = Church::factory()->create();
    $user = User::factory()->forChurch($a)->create();

    $this->actingAs($user);

    $song = Song::create(['name' => 'Stamped Song']);

    expect($song->church_id)->toBe($a->id);
});
