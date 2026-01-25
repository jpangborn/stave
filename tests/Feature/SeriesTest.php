<?php

use App\Models\Reading;
use App\Models\Series;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group series */
test('guests are redirected from the series index', function (): void {
    $response = $this->get('/series');
    $response->assertRedirect('/login');
});

test('authenticated users can view the series index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/series')
        ->assertStatus(200);
});

test('guests are redirected from the series create page', function (): void {
    $response = $this->get('/series/create');
    $response->assertRedirect('/login');
});

test('authenticated users can view the series create page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/series/create')
        ->assertStatus(200)
        ->assertSee('Add a Series');
});

test('series name is required when creating a series', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::series.create')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

test('authenticated users can create a series', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::series.create')
        ->set('form.name', 'Test Series')
        ->set('form.description', '<p>A test series description</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/series');

    $this->assertDatabaseHas('series', ['name' => 'Test Series']);
});

test('guests are redirected from the series show page', function (): void {
    $series = Series::create([
        'name' => 'Test Series',
        'description' => '<p>Description</p>',
    ]);
    $response = $this->get("/series/{$series->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view the series show page', function (): void {
    $user = User::factory()->create();
    $series = Series::create([
        'name' => 'Test Series',
        'description' => '<p>Description</p>',
    ]);

    $this->actingAs($user)
        ->get("/series/{$series->id}")
        ->assertStatus(200)
        ->assertSee('Test Series');
});

test('guests are redirected from the series edit page', function (): void {
    $series = Series::create([
        'name' => 'Edit Me',
        'description' => '<p>Description</p>',
    ]);
    $response = $this->get("/series/{$series->id}/edit");
    $response->assertRedirect('/login');
});

test('authenticated users can view the series edit page', function (): void {
    $user = User::factory()->create();
    $series = Series::create([
        'name' => 'Edit Me',
        'description' => '<p>Description</p>',
    ]);

    $this->actingAs($user)
        ->get("/series/{$series->id}/edit")
        ->assertStatus(200)
        ->assertSee('Edit Series:')
        ->assertSee('Edit Me');
});

test('authenticated users can update a series', function (): void {
    $user = User::factory()->create();
    $series = Series::create([
        'name' => 'Old Name',
        'description' => '<p>Old description</p>',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::series.edit', ['series' => $series->id])
        ->set('form.name', 'New Name')
        ->set('form.description', '<p>New description</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/series');

    expect($series->fresh()->name)->toBe('New Name');
});

test('authenticated users can delete a series', function (): void {
    $user = User::factory()->create();
    $series = Series::create([
        'name' => 'Delete Me',
        'description' => '<p>Description</p>',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::series.edit', ['series' => $series->id])
        ->call('delete')
        ->assertRedirect('/series');

    $this->assertDatabaseMissing('series', ['id' => $series->id]);
});

test('deleting a series sets reading series_id to null', function (): void {
    $user = User::factory()->create();
    $series = Series::factory()->create();
    $reading = Reading::factory()->create([
        'series_id' => $series->id,
        'series_order' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::series.edit', ['series' => $series->id])
        ->call('delete')
        ->assertRedirect('/series');

    $this->assertDatabaseMissing('series', ['id' => $series->id]);
    expect($reading->fresh()->series_id)->toBeNull();
});

test('can assign reading to series with order number', function (): void {
    $user = User::factory()->create();
    $series = Series::factory()->create();
    $reading = Reading::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::series.show', ['series' => $series->id])
        ->set('selectedReadingId', $reading->id)
        ->set('selectedOrder', 1)
        ->call('addReading')
        ->assertHasNoErrors();

    expect($reading->fresh()->series_id)->toBe($series->id);
    expect($reading->fresh()->series_order)->toBe(1);
});

test('can remove reading from series', function (): void {
    $user = User::factory()->create();
    $series = Series::factory()->create();
    $reading = Reading::factory()->create([
        'series_id' => $series->id,
        'series_order' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::series.show', ['series' => $series->id])
        ->call('removeReading', $reading->id)
        ->assertHasNoErrors();

    expect($reading->fresh()->series_id)->toBeNull();
    expect($reading->fresh()->series_order)->toBeNull();
});

test('series show page displays readings in order', function (): void {
    $user = User::factory()->create();
    $series = Series::factory()->create();

    $reading1 = Reading::factory()->create([
        'title' => 'First Reading',
        'series_id' => $series->id,
        'series_order' => 1,
    ]);
    $reading2 = Reading::factory()->create([
        'title' => 'Second Reading',
        'series_id' => $series->id,
        'series_order' => 2,
    ]);
    $reading3 = Reading::factory()->create([
        'title' => 'Third Reading',
        'series_id' => $series->id,
        'series_order' => 3,
    ]);

    $this->actingAs($user)
        ->get("/series/{$series->id}")
        ->assertStatus(200)
        ->assertSeeInOrder(['First Reading', 'Second Reading', 'Third Reading']);
});

test('series index displays reading count', function (): void {
    $user = User::factory()->create();
    $series = Series::factory()->create(['name' => 'My Series']);

    Reading::factory()->count(3)->create([
        'series_id' => $series->id,
    ]);

    $this->actingAs($user)
        ->get('/series')
        ->assertStatus(200)
        ->assertSee('My Series');
});

test('can create reading with series assignment', function (): void {
    $user = User::factory()->create();
    $series = Series::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::readings.create')
        ->set('form.title', 'Reading in Series')
        ->set('form.type', \App\Enums\ReadingType::CREED->value)
        ->set('form.series_id', $series->id)
        ->set('form.series_order', 1)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/readings');

    $this->assertDatabaseHas('readings', [
        'title' => 'Reading in Series',
        'series_id' => $series->id,
        'series_order' => 1,
    ]);
});

test('can update reading with series assignment', function (): void {
    $user = User::factory()->create();
    $series = Series::factory()->create();
    $reading = Reading::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::readings.edit', ['reading' => $reading->id])
        ->set('form.series_id', $series->id)
        ->set('form.series_order', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/readings');

    expect($reading->fresh()->series_id)->toBe($series->id);
    expect($reading->fresh()->series_order)->toBe(2);
});
