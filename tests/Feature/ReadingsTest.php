<?php

use App\Models\Reading;
use App\Models\User;
use App\Enums\ReadingType;
use Livewire\Volt\Volt as LivewireVolt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group readings */
test('guests are redirected from the readings index', function () {
    $response = $this->get('/readings');
    $response->assertRedirect('/login');
});

test('authenticated users can view the readings index', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/readings')
        ->assertStatus(200);
});

test('guests are redirected from the reading create page', function () {
    $response = $this->get('/readings/create');
    $response->assertRedirect('/login');
});

test('authenticated users can view the reading create page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/readings/create')
        ->assertStatus(200)
        ->assertSee('Add a Reading');
});

test('reading title is required when creating a reading', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('readings.create')
        ->set('form.type', ReadingType::WORSHIP_CALL->value)
        ->call('save')
        ->assertHasErrors(['form.title' => 'required']);
});

test('reading type is required when creating a reading', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('readings.create')
        ->set('form.title', 'Test Reading')
        ->call('save')
        ->assertHasErrors(['form.type' => 'required']);
});

test('authenticated users can create a reading', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('readings.create')
        ->set('form.title', 'Test Reading')
        ->set('form.type', ReadingType::CREED->value)
        ->set('form.text', '<p>Some text</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/readings');

    $this->assertDatabaseHas('readings', ['title' => 'Test Reading']);
});

test('guests are redirected from the reading show page', function () {
    $reading = Reading::create([
        'title' => 'Test Reading',
        'type' => ReadingType::CREED->value,
        'text' => '<p>Text</p>',
    ]);
    $response = $this->get("/readings/{$reading->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view the reading show page', function () {
    $user = User::factory()->create();
    $reading = Reading::create([
        'title' => 'Test Reading',
        'type' => ReadingType::CREED->value,
        'text' => '<p>Text</p>',
    ]);

    $this->actingAs($user)
        ->get("/readings/{$reading->id}")
        ->assertStatus(200)
        ->assertSee('Test Reading');
});

test('guests are redirected from the reading edit page', function () {
    $reading = Reading::create([
        'title' => 'Edit Me',
        'type' => ReadingType::LAW->value,
        'text' => '<p>Text</p>',
    ]);
    $response = $this->get("/readings/{$reading->id}/edit");
    $response->assertRedirect('/login');
});

test('authenticated users can view the reading edit page', function () {
    $user = User::factory()->create();
    $reading = Reading::create([
        'title' => 'Edit Me',
        'type' => ReadingType::LAW->value,
        'text' => '<p>Text</p>',
    ]);

    $this->actingAs($user)
        ->get("/readings/{$reading->id}/edit")
        ->assertStatus(200)
        ->assertSee('Edit Reading:')
        ->assertSee('Edit Me');
});

test('authenticated users can update a reading', function () {
    $user = User::factory()->create();
    $reading = Reading::create([
        'title' => 'Old Title',
        'type' => ReadingType::PRAYER->value,
        'text' => '<p>Old text</p>',
    ]);

    $this->actingAs($user);

    LivewireVolt::test('readings.edit', ['reading' => $reading->id])
        ->set('form.title', 'New Title')
        ->set('form.text', '<p>New text</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/readings');

    expect($reading->fresh()->title)->toBe('New Title');
});

test('authenticated users can delete a reading', function () {
    $user = User::factory()->create();
    $reading = Reading::create([
        'title' => 'Delete Me',
        'type' => ReadingType::PRAISE->value,
        'text' => '<p>Text</p>',
    ]);

    $this->actingAs($user);

    LivewireVolt::test('readings.edit', ['reading' => $reading->id])
        ->call('delete')
        ->assertRedirect('/readings');

    $this->assertDatabaseMissing('readings', ['id' => $reading->id]);
});