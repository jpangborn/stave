<?php

use App\Enums\ReadingType;
use App\Models\Reading;
use App\Models\Service;
use App\Models\User;
use Livewire\Volt\Volt as LivewireVolt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group readings */
test('guests are redirected from the readings index', function (): void {
    $response = $this->get('/readings');
    $response->assertRedirect('/login');
});

test('authenticated users can view the readings index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/readings')
        ->assertStatus(200);
});

test('guests are redirected from the reading create page', function (): void {
    $response = $this->get('/readings/create');
    $response->assertRedirect('/login');
});

test('authenticated users can view the reading create page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/readings/create')
        ->assertStatus(200)
        ->assertSee('Add a Reading');
});

test('reading title is required when creating a reading', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('readings.create')
        ->set('form.type', ReadingType::WORSHIP_CALL->value)
        ->call('save')
        ->assertHasErrors(['form.title' => 'required']);
});

test('reading type is required when creating a reading', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('readings.create')
        ->set('form.title', 'Test Reading')
        ->call('save')
        ->assertHasErrors(['form.type' => 'required']);
});

test('authenticated users can create a reading', function (): void {
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

test('guests are redirected from the reading show page', function (): void {
    $reading = Reading::create([
        'title' => 'Test Reading',
        'type' => ReadingType::CREED->value,
        'text' => '<p>Text</p>',
    ]);
    $response = $this->get("/readings/{$reading->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view the reading show page', function (): void {
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

test('guests are redirected from the reading edit page', function (): void {
    $reading = Reading::create([
        'title' => 'Edit Me',
        'type' => ReadingType::LAW->value,
        'text' => '<p>Text</p>',
    ]);
    $response = $this->get("/readings/{$reading->id}/edit");
    $response->assertRedirect('/login');
});

test('authenticated users can view the reading edit page', function (): void {
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

test('authenticated users can update a reading', function (): void {
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

test('authenticated users can delete a reading', function (): void {
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

test('readings index displays last used date when reading is used in a service', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create(['title' => 'Used Reading']);
    $service = Service::factory()->create(['date' => now()->subDays(5)]);

    // Create a liturgy element linking the reading to the service
    $service->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::READING,
        'reading_type' => ReadingType::WORSHIP_CALL,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
        'order' => 1,
        'name' => 'Call to Worship',
    ]);

    $response = $this->actingAs($user)
        ->get('/readings');

    $response->assertStatus(200)
        ->assertSee('Used Reading');

    // Check that the last used date is set correctly
    $readingWithDate = Reading::withLastUsedDate()->find($reading->id);
    $this->assertNotNull($readingWithDate->last_used_date);
    $this->assertEquals($service->date->toDateString(), $readingWithDate->last_used_date->toDateString());
});

test('readings index displays Never for readings never used in services', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create(['title' => 'Unused Reading']);

    $this->actingAs($user)
        ->get('/readings')
        ->assertStatus(200)
        ->assertSee('Unused Reading')
        ->assertSee('Never');
});

test('readings index sorting by last used date works correctly', function (): void {
    $user = User::factory()->create();

    // Create readings
    $readingNeverUsed = Reading::factory()->create(['title' => 'Never Used Reading']);
    $readingUsedRecently = Reading::factory()->create(['title' => 'Recently Used Reading']);
    $readingUsedLongAgo = Reading::factory()->create(['title' => 'Old Used Reading']);

    // Create services with different dates
    $recentService = Service::factory()->create(['date' => now()->subDays(2)]);
    $oldService = Service::factory()->create(['date' => now()->subDays(30)]);

    // Link readings to services
    $recentService->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::READING,
        'reading_type' => ReadingType::CONFESSION,
        'content_type' => Reading::class,
        'content_id' => $readingUsedRecently->id,
        'order' => 1,
        'name' => 'Reading',
    ]);

    $oldService->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::READING,
        'reading_type' => ReadingType::CONFESSION,
        'content_type' => Reading::class,
        'content_id' => $readingUsedLongAgo->id,
        'order' => 1,
        'name' => 'Reading',
    ]);

    $this->actingAs($user);

    // Test descending sort (recently used first, never used last)
    LivewireVolt::test('readings.index')
        ->set('sortBy', 'last_used_date')
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder([
            'Recently Used Reading',
            'Old Used Reading',
            'Never Used Reading',
        ]);

    // Test ascending sort (never used first, recently used last)
    LivewireVolt::test('readings.index')
        ->set('sortBy', 'last_used_date')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder([
            'Never Used Reading',
            'Old Used Reading',
            'Recently Used Reading',
        ]);
})->skip('Unknown Test Issue');

test('readings used in templates do not show last used date', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create(['title' => 'Template Reading']);
    $template = \App\Models\Template::factory()->create();

    // Create a liturgy element linking the reading to the template (not service)
    $template->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::READING,
        'reading_type' => ReadingType::PRAISE,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
        'order' => 1,
        'name' => 'Template Reading',
    ]);

    $this->actingAs($user)
        ->get('/readings')
        ->assertStatus(200)
        ->assertSee('Template Reading')
        ->assertSee('Never');
});

test('readings do not count future services for last used date', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create(['title' => 'Future Reading']);
    $futureService = Service::factory()->create(['date' => now()->addDays(7)]);

    // Create a liturgy element linking the reading to a future service
    $futureService->liturgyElements()->create([
        'type' => \App\Enums\LiturgyElementType::READING,
        'reading_type' => ReadingType::BENEDICTION,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
        'order' => 1,
        'name' => 'Future Reading',
    ]);

    $this->actingAs($user)
        ->get('/readings')
        ->assertStatus(200)
        ->assertSee('Future Reading')
        ->assertSee('Never');
});
