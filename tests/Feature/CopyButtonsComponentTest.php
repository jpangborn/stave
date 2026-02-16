<?php

use App\Enums\LiturgyElementType;
use App\Models\Reading;
use App\Models\Service;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group copy-buttons */
test('copy-buttons component renders with content prop', function (): void {
    $html = Blade::render('<x-copy-buttons :content="$content" />', [
        'content' => '<p>Test content</p>',
    ]);

    expect($html)
        ->toContain('Copy with paragraph spacing')
        ->toContain('Copy compact (single line breaks)')
        ->toContain('x-data');
});

test('copy-buttons component renders with empty props', function (): void {
    $html = Blade::render('<x-copy-buttons />');

    expect($html)
        ->toContain('Copy with paragraph spacing')
        ->toContain('Copy compact (single line breaks)');
});

test('copy-buttons component accepts custom classes', function (): void {
    $html = Blade::render('<x-copy-buttons class="custom-class" />');

    expect($html)->toContain('custom-class');
});

test('copy-buttons component escapes HTML in content for JavaScript', function (): void {
    $html = Blade::render('<x-copy-buttons :content="$content" />', [
        'content' => '<p>Content with <strong>HTML</strong></p>',
    ]);

    // The component should render without errors
    expect($html)->toContain('x-data');
});

test('bulletin tab shows copy buttons for songs with content', function (): void {
    $user = User::factory()->create();
    $song = Song::factory()->create([
        'name' => 'Amazing Grace',
        'lyrics' => '<p>Amazing grace, how sweet the sound</p>',
    ]);
    $service = Service::factory()->create();
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $song->id,
        'order' => 1,
        'name' => 'Opening Song',
    ]);

    $this->actingAs($user);

    Livewire::test('services.bulletin', ['serviceId' => $service->id])
        ->assertSee('Copy with paragraph spacing')
        ->assertSee('Amazing Grace');
});

test('bulletin tab shows copy buttons for readings with content', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create([
        'title' => 'Psalm 23',
        'text' => '<p>The Lord is my shepherd</p>',
    ]);
    $service = Service::factory()->create();
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::READING,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
        'order' => 1,
        'name' => 'Scripture Reading',
    ]);

    $this->actingAs($user);

    Livewire::test('services.bulletin', ['serviceId' => $service->id])
        ->assertSee('Copy with paragraph spacing')
        ->assertSee('Psalm 23');
});

test('bulletin tab does not show copy buttons for songs without content', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => null,
        'content_id' => null,
        'order' => 1,
        'name' => 'Opening Song',
    ]);

    $this->actingAs($user);

    Livewire::test('services.bulletin', ['serviceId' => $service->id])
        ->assertSee('Opening Song')
        ->assertDontSee('Copy with paragraph spacing');
});

test('podium notes tab shows copy buttons for readings with content', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create([
        'title' => 'Romans 8',
        'text' => '<p>There is no condemnation</p>',
    ]);
    $service = Service::factory()->create();
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::READING,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
        'order' => 1,
        'name' => 'Epistle Reading',
    ]);

    $this->actingAs($user);

    Livewire::test('services.podium-notes', ['serviceId' => $service->id])
        ->assertSee('Copy with paragraph spacing')
        ->assertSee('Romans 8');
});

test('podium notes tab shows copy buttons for prayers with content', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create([
        'title' => 'Prayer of Confession',
        'text' => '<p>Almighty God, we confess...</p>',
    ]);
    $service = Service::factory()->create();
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::PRAYER,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
        'order' => 1,
        'name' => 'Confession',
    ]);

    $this->actingAs($user);

    Livewire::test('services.podium-notes', ['serviceId' => $service->id])
        ->assertSee('Copy with paragraph spacing')
        ->assertSee('Prayer of Confession');
});
