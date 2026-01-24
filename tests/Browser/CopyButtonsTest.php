<?php

use App\Enums\LiturgyElementType;
use App\Models\Reading;
use App\Models\Service;
use App\Models\Song;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group copy-buttons */
/** @group browser */
it('renders bulletin tab with copy buttons for songs without JavaScript errors', function (): void {
    $user = User::factory()->create();
    $song = Song::factory()->create([
        'name' => 'Amazing Grace',
        'lyrics' => '<p>Amazing grace, how sweet the sound</p><p>That saved a wretch like me</p>',
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

    $page = visit("/services/{$service->id}?tab=bulletin");

    $page->assertSee('Amazing Grace')
        ->assertNoJavascriptErrors();
});

it('renders bulletin tab with copy buttons for readings without JavaScript errors', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create([
        'title' => 'Psalm 23',
        'text' => '<p>The Lord is my shepherd; I shall not want.</p>',
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

    $page = visit("/services/{$service->id}?tab=bulletin");

    $page->assertSee('Psalm 23')
        ->assertNoJavascriptErrors();
});

it('renders podium notes tab with copy buttons without JavaScript errors', function (): void {
    $user = User::factory()->create();
    $reading = Reading::factory()->create([
        'title' => 'Romans 8:1',
        'text' => '<p>There is therefore now no condemnation for those who are in Christ Jesus.</p>',
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

    $page = visit("/services/{$service->id}?tab=podium-notes");

    $page->assertSee('Romans 8:1')
        ->assertNoJavascriptErrors();
});

it('renders bulletin tab with multiple copy buttons without JavaScript errors', function (): void {
    $user = User::factory()->create();
    $song = Song::factory()->create([
        'name' => 'Holy Holy Holy',
        'lyrics' => '<p>Holy, holy, holy! Lord God Almighty!</p>',
    ]);
    $reading = Reading::factory()->create([
        'title' => 'Isaiah 6:3',
        'text' => '<p>And one called to another and said: Holy, holy, holy is the Lord of hosts</p>',
    ]);
    $service = Service::factory()->create();

    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $song->id,
        'order' => 1,
        'name' => 'Opening Hymn',
    ]);

    $service->liturgyElements()->create([
        'type' => LiturgyElementType::READING,
        'content_type' => Reading::class,
        'content_id' => $reading->id,
        'order' => 2,
        'name' => 'Old Testament Reading',
    ]);

    $this->actingAs($user);

    $page = visit("/services/{$service->id}?tab=bulletin");

    $page->assertSee('Holy Holy Holy')
        ->assertSee('Isaiah 6:3')
        ->assertNoJavascriptErrors();
});
