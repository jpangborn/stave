<?php

use App\Enums\LiturgyElementType;
use App\Models\Service;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @group browser */
it('renders the public bulletin for guests without smoke', function (): void {
    $preacher = User::factory()->create(['name' => 'Rev. Smoke Tester']);

    $song = Song::factory()->create([
        'name' => 'Doxology',
        'lyrics' => '<p>Praise God, from whom all blessings flow</p>',
        'ccli_number' => '56204',
    ]);

    $service = Service::factory()->create(['title' => 'Sunday Morning Service', 'date' => now()]);
    $service->liturgyElements()->createMany([
        ['type' => LiturgyElementType::SECTION, 'name' => 'God', 'description' => 'We start with a proclamation of God.', 'order' => 1],
        ['type' => LiturgyElementType::SONG, 'name' => 'Doxology', 'order' => 2, 'content_type' => Song::class, 'content_id' => $song->id],
        ['type' => LiturgyElementType::SECTION, 'name' => 'Word', 'description' => 'God speaks to his people.', 'order' => 3],
        ['type' => LiturgyElementType::SERMON, 'name' => 'The God Who Provides', 'order' => 4, 'assignee_id' => $preacher->id],
    ]);

    $page = visit(route('bulletin.show', [$service->church, $service]));

    $page->assertSee('Reforming Truth Church')
        ->assertSee('Doxology')
        ->assertSee('Praise God, from whom all blessings flow')
        ->assertSee('1 of 2')
        ->assertNoJavascriptErrors()
        ->assertNoSmoke();
});

/** @group browser */
it('advances to the next element with the arrow key', function (): void {
    $service = Service::factory()->create(['date' => now()]);
    $service->liturgyElements()->createMany([
        ['type' => LiturgyElementType::READING, 'name' => 'Call to Worship', 'order' => 1],
        ['type' => LiturgyElementType::SERMON, 'name' => 'The God Who Provides', 'order' => 2],
    ]);

    $page = visit(route('bulletin.show', [$service->church, $service]));

    $page->assertSee('Call to Worship')
        ->keys('#follow-along', 'ArrowRight')
        ->assertSee('The God Who Provides')
        ->assertSee('2 of 2')
        ->assertNoJavascriptErrors();
});
