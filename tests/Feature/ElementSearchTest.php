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
        ->set('contentSearch', 'Grace')
        ->get('songs');

    expect($songs)->toHaveCount(1);
    expect($songs->first()->name)->toBe('Amazing Grace');
});

it('limits song results to 50', function (): void {
    Song::factory()
        ->count(60)
        ->sequence(fn ($sequence) => ['name' => "Hymn {$sequence->index}"])
        ->create();

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::SONG,
    ]);

    $songs = Livewire::test('elements.song', ['element' => $element])
        ->set('contentSearch', 'Hymn')
        ->get('songs');

    expect($songs)->toHaveCount(50);
});

it('preloads recent songs when no search term is entered', function (): void {
    Song::factory()->count(10)->create();

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::SONG,
    ]);

    $songs = Livewire::test('elements.song', ['element' => $element])->get('songs');

    expect($songs)->toHaveCount(10);
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
        ->set('contentSearch', 'Psalm')
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
        ->set('contentSearch', 'Benediction')
        ->get('readings');

    expect($readings)->toHaveCount(1);
    expect($readings->first()->id)->toBe($match->id);
});

it('preloads recent readings when no search term is entered', function (): void {
    Reading::factory()->count(10)->create();

    $service = Service::factory()->create();
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
    ]);

    $readings = Livewire::test('elements.reading', ['element' => $element])->get('readings');

    expect($readings)->toHaveCount(10);
});
