<?php

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use App\Models\LiturgyElement;
use App\Models\Reading;
use App\Models\Service;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('filters songs by search term', function (): void {
    Song::factory()->create(['name' => 'Amazing Grace']);
    Song::factory()->create(['name' => 'How Great Thou Art']);
    Song::factory()->create(['name' => 'Be Thou My Vision']);

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::SONG,
    ]);

    $songs = Livewire::test('elements.song', ['element' => $element])
        ->set('search', 'Grace')
        ->get('songs');

    expect($songs)->toHaveCount(1);
    expect($songs->first()->name)->toBe('Amazing Grace');
});

it('limits song results to 50', function (): void {
    Song::factory()->count(60)->create();

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::SONG,
    ]);

    $songs = Livewire::test('elements.song', ['element' => $element])->get('songs');

    expect($songs)->toHaveCount(50);
});

it('keeps the selected song in the list when the search would exclude it', function (): void {
    $selected = Song::factory()->create(['name' => 'Selected Song']);
    Song::factory()->create(['name' => 'Other Song']);

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $selected->id,
    ]);

    $songs = Livewire::test('elements.song', ['element' => $element])
        ->set('search', 'Other')
        ->get('songs');

    expect($songs->pluck('id'))->toContain($selected->id);
});

it('filters readings by search term', function (): void {
    Reading::factory()->create(['title' => 'Psalm 23', 'type' => ReadingType::PRAISE]);
    Reading::factory()->create(['title' => 'Psalm 100', 'type' => ReadingType::PRAISE]);
    Reading::factory()->create(['title' => 'Romans 8', 'type' => ReadingType::PRAISE]);

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
    ]);

    $readings = Livewire::test('elements.reading', ['element' => $element])
        ->set('search', 'Psalm')
        ->get('readings');

    expect($readings)->toHaveCount(2);
    expect($readings->pluck('title')->all())->toEqualCanonicalizing(['Psalm 23', 'Psalm 100']);
});

it('combines reading_type filter with search term', function (): void {
    $match = Reading::factory()->create([
        'title' => 'Benediction Verse',
        'type' => ReadingType::BENEDICTION,
    ]);
    Reading::factory()->create([
        'title' => 'Benediction Other',
        'type' => ReadingType::CREED,
    ]);
    Reading::factory()->create([
        'title' => 'Different Title',
        'type' => ReadingType::BENEDICTION,
    ]);

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
        'reading_type' => ReadingType::BENEDICTION,
    ]);

    $readings = Livewire::test('elements.reading', ['element' => $element])
        ->set('search', 'Benediction')
        ->get('readings');

    expect($readings)->toHaveCount(1);
    expect($readings->first()->id)->toBe($match->id);
});

it('keeps the selected reading in the list when the search would exclude it', function (): void {
    $selected = Reading::factory()->create([
        'title' => 'Selected Reading',
        'type' => ReadingType::PRAYER,
    ]);
    Reading::factory()->create([
        'title' => 'Other Reading',
        'type' => ReadingType::PRAYER,
    ]);

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
        'content_type' => Reading::class,
        'content_id' => $selected->id,
    ]);

    $readings = Livewire::test('elements.reading', ['element' => $element])
        ->set('search', 'Other')
        ->get('readings');

    expect($readings->pluck('id'))->toContain($selected->id);
});
