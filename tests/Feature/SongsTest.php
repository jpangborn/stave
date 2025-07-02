<?php

use App\Models\Song;
use App\Models\User;
use Livewire\Volt\Volt as LivewireVolt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group songs */
test('guests are redirected from the songs index', function () {
    $response = $this->get('/songs');
    $response->assertRedirect('/login');
});

test('authenticated users can view the songs index', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/songs')
        ->assertStatus(200);
});

test('guests are redirected from the song create page', function () {
    $response = $this->get('/songs/create');
    $response->assertRedirect('/login');
});

test('authenticated users can view the song create page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/songs/create')
        ->assertStatus(200)
        ->assertSee('Add a Song');
});

test('song name is required when creating a song', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('songs.create')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

test('authenticated users can create a song', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('songs.create')
        ->set('form.name', 'Amazing Grace')
        ->set('form.ccli_number', '12345')
        ->set('form.copyright', 'Public Domain')
        ->set('form.lyrics', 'Lorem ipsum')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/songs');

    $this->assertDatabaseHas('songs', ['name' => 'Amazing Grace']);
});

test('guests are redirected from the song show page', function () {
    $song = Song::create([
        'name' => 'Test Song',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);
    $response = $this->get("/songs/{$song->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view the song show page', function () {
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

test('guests are redirected from the song edit page', function () {
    $song = Song::create([
        'name' => 'Edit Me',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);
    $response = $this->get("/songs/{$song->id}/edit");
    $response->assertRedirect('/login');
});

test('authenticated users can view the song edit page', function () {
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

test('authenticated users can update a song', function () {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Old Title',
        'ccli_number' => '11111',
        'copyright' => '©',
        'lyrics' => 'Old lyrics',
    ]);

    $this->actingAs($user);

    LivewireVolt::test('songs.edit', ['song' => $song->id])
        ->set('form.name', 'New Title')
        ->set('form.lyrics', 'New lyrics')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/songs');

    expect($song->fresh()->name)->toBe('New Title');
});

test('authenticated users can delete a song', function () {
    $user = User::factory()->create();
    $song = Song::create([
        'name' => 'Delete Me',
        'ccli_number' => null,
        'copyright' => null,
        'lyrics' => null,
    ]);

    $this->actingAs($user);

    LivewireVolt::test('songs.edit', ['song' => $song->id])
        ->call('delete')
        ->assertRedirect('/songs');

    $this->assertDatabaseMissing('songs', ['id' => $song->id]);
});
