<?php

use App\Models\Service;
use App\Models\Song;
use App\Models\Template;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group songs */
test('guests are redirected from the songs index', function (): void {
    $response = $this->get('/songs');
    $response->assertRedirect('/login');
});

test('authenticated users can view the songs index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/songs')
        ->assertStatus(200);
});

test('guests are redirected from the song create page', function (): void {
    $response = $this->get('/songs/create');
    $response->assertRedirect('/login');
});

test('authenticated users can view the song create page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/songs/create')
        ->assertStatus(200)
        ->assertSee('Add a Song');
});

test('song name is required when creating a song', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::songs.create')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

test('authenticated users can create a song', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::songs.create')
        ->set('form.name', 'Amazing Grace')
        ->set('form.ccli_number', '12345')
        ->set('form.copyright', 'Public Domain')
        ->set('form.lyrics', 'Lorem ipsum')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/songs');

    $this->assertDatabaseHas('songs', ['name' => 'Amazing Grace']);
});

test('guests are redirected from the song show page', function (): void {
    $song = Song::create([
        'name' => 'Test Song',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);
    $response = $this->get("/songs/{$song->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view the song show page', function (): void {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Test Song',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);

    $this->actingAs($user)
        ->get("/songs/{$song->id}")
        ->assertStatus(200)
        ->assertSee('Test Song');
});

test('guests are redirected from the song edit page', function (): void {
    $song = Song::create([
        'name' => 'Edit Me',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);
    $response = $this->get("/songs/{$song->id}/edit");
    $response->assertRedirect('/login');
});

test('authenticated users can view the song edit page', function (): void {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Edit Me',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);

    $this->actingAs($user)
        ->get("/songs/{$song->id}/edit")
        ->assertStatus(200)
        ->assertSee('Edit Song:')
        ->assertSee('Edit Me');
});

test('authenticated users can update a song', function (): void {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Old Title',
        'ccli_number' => '11111',
        'copyright' => '©',
        'lyrics' => 'Old lyrics',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::songs.edit', ['song' => $song->id])
        ->set('form.name', 'New Title')
        ->set('form.lyrics', 'New lyrics')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/songs');

    expect($song->fresh()->name)->toBe('New Title');
});

test('authenticated users can delete a song', function (): void {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Delete Me',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::songs.edit', ['song' => $song->id])
        ->call('delete')
        ->assertRedirect('/songs');

    $this->assertDatabaseMissing('songs', ['id' => $song->id]);
});

test('authenticated users can create a song with authors', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::songs.create')
        ->set('form.name', 'Amazing Grace')
        ->set('form.authors', 'John Newton, William Cowper')
        ->set('form.ccli_number', '12345')
        ->set('form.copyright', 'Public Domain')
        ->set('form.lyrics', 'Lorem ipsum')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/songs');

    $this->assertDatabaseHas('songs', [
        'name' => 'Amazing Grace',
        'authors' => 'John Newton, William Cowper',
    ]);
});

test('authenticated users can update a song with authors', function (): void {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Old Title',
        'authors' => null,
        'ccli_number' => '11111',
        'copyright' => '©',
        'lyrics' => 'Old lyrics',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::songs.edit', ['song' => $song->id])
        ->set('form.name', 'New Title')
        ->set('form.authors', 'John Smith, Jane Doe')
        ->set('form.lyrics', 'New lyrics')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/songs');

    expect($song->fresh()->authors)->toBe('John Smith, Jane Doe');
});

test('song authors field is optional', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::songs.create')
        ->set('form.name', 'Song Without Authors')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/songs');

    $this->assertDatabaseHas('songs', [
        'name' => 'Song Without Authors',
        'authors' => null,
    ]);
});

test('song show page displays authors when present', function (): void {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Test Song',
        'authors' => 'John Newton, William Cowper',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);

    $this->actingAs($user)
        ->get("/songs/{$song->id}")
        ->assertStatus(200)
        ->assertSee('John Newton, William Cowper');
});

test('songs index displays last used date when song is used in a service', function (): void {
    $user = User::factory()->create();
    $song = Song::factory()->create(['name' => 'Used Song']);
    $service = Service::factory()->create(['date' => now()->subDays(5)]);

    // Create a liturgy element linking the song to the service
    $element = $service->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $song->id,
        'order' => 1,
        'name' => 'Opening Song',
    ]);

    $response = $this->actingAs($user)
        ->get('/songs');

    $response->assertStatus(200)
        ->assertSee('Used Song');

    // Check that the last used date is set correctly
    $songWithDate = Song::withLastUsedDate()->find($song->id);
    $this->assertNotNull($songWithDate->last_used_date);
    $this->assertEquals($service->date->toDateString(), $songWithDate->last_used_date->toDateString());
});

test('songs index displays Never for songs never used in services', function (): void {
    $user = User::factory()->create();
    $song = Song::factory()->create(['name' => 'Unused Song']);

    $this->actingAs($user)
        ->get('/songs')
        ->assertStatus(200)
        ->assertSee('Unused Song')
        ->assertSee('Never');
});

test('songs index sorting by last used date works correctly', function (): void {
    $user = User::factory()->create();

    // Create songs
    $songNeverUsed = Song::factory()->create(['name' => 'Never Used Song']);
    $songUsedRecently = Song::factory()->create(['name' => 'Recently Used Song']);
    $songUsedLongAgo = Song::factory()->create(['name' => 'Old Used Song']);

    // Create services with different dates
    $recentService = Service::factory()->create(['date' => now()->subDays(2)]);
    $oldService = Service::factory()->create(['date' => now()->subDays(30)]);

    // Link songs to services
    $recentService->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $songUsedRecently->id,
        'order' => 1,
        'name' => 'Song',
    ]);

    $oldService->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $songUsedLongAgo->id,
        'order' => 1,
        'name' => 'Song',
    ]);

    $this->actingAs($user);

    // Test descending sort (recently used first, never used last)
    Livewire::test('pages::songs.index')
        ->set('sortBy', 'last_used_date')
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder([
            'Recently Used Song',
            'Old Used Song',
            'Never Used Song',
        ]);

    // Test ascending sort (never used first, recently used last)
    Livewire::test('pages::songs.index')
        ->set('sortBy', 'last_used_date')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder([
            'Never Used Song',
            'Old Used Song',
            'Recently Used Song',
        ]);
})->skip('Unknown Test Issue');

test('songs used in templates do not show last used date', function (): void {
    $user = User::factory()->create();
    $song = Song::factory()->create(['name' => 'Template Song']);
    $template = Template::factory()->create();

    // Create a liturgy element linking the song to the template (not service)
    $template->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $song->id,
        'order' => 1,
        'name' => 'Template Song',
    ]);

    $this->actingAs($user)
        ->get('/songs')
        ->assertStatus(200)
        ->assertSee('Template Song')
        ->assertSee('Never');
});

test('songs do not count future services for last used date', function (): void {
    $user = User::factory()->create();
    $song = Song::factory()->create(['name' => 'Future Song']);
    $futureService = Service::factory()->create(['date' => now()->addDays(7)]);

    // Create a liturgy element linking the song to a future service
    $futureService->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $song->id,
        'order' => 1,
        'name' => 'Future Song',
    ]);

    $this->actingAs($user)
        ->get('/songs')
        ->assertStatus(200)
        ->assertSee('Future Song')
        ->assertSee('Never');
});
