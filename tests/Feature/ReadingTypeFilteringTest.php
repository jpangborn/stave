<?php

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use App\Models\LiturgyElement;
use App\Models\Reading;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('filters readings by reading_type when element has reading_type specified', function () {
    $callToWorshipReading = Reading::factory()->create([
        'title' => 'Call to Worship Reading',
        'type' => ReadingType::WORSHIP_CALL,
    ]);

    Reading::factory()->create([
        'title' => 'Law Reading',
        'type' => ReadingType::LAW,
    ]);

    Reading::factory()->create([
        'title' => 'Other Reading',
        'type' => ReadingType::CREED,
    ]);

    $service = Service::factory()->create();
    $readingElement = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
        'reading_type' => ReadingType::WORSHIP_CALL,
    ]);

    $readings = Livewire::test('elements.reading', ['element' => $readingElement])
        ->set('contentSearch', 'Reading')
        ->get('readings');

    expect($readings)->toHaveCount(1);
    expect($readings->first()->id)->toBe($callToWorshipReading->id);
    expect($readings->first()->title)->toBe('Call to Worship Reading');
});

it('returns all readings matching search when element has no reading_type specified', function () {
    Reading::factory()->create(['title' => 'Worship Reading', 'type' => ReadingType::WORSHIP_CALL]);
    Reading::factory()->create(['title' => 'Law Reading', 'type' => ReadingType::LAW]);
    Reading::factory()->create(['title' => 'Creed Reading', 'type' => ReadingType::CREED]);

    $service = Service::factory()->create();
    $readingElement = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
        'reading_type' => null,
    ]);

    $readings = Livewire::test('elements.reading', ['element' => $readingElement])
        ->set('contentSearch', 'Reading')
        ->get('readings');

    expect($readings)->toHaveCount(3);
});
