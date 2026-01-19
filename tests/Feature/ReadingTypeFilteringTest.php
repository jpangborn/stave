<?php

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use App\Models\LiturgyElement;
use App\Models\Reading;
use App\Models\Service;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('filters readings by reading_type when element has reading_type specified', function () {
    // Create readings of different types
    $callToWorshipReading = Reading::factory()->create([
        'title' => 'Call to Worship Reading',
        'type' => ReadingType::WORSHIP_CALL,
    ]);

    $lawReading = Reading::factory()->create([
        'title' => 'Law Reading',
        'type' => ReadingType::LAW,
    ]);

    $otherReading = Reading::factory()->create([
        'title' => 'Other Reading',
        'type' => ReadingType::CREED,
    ]);

    // Create a service with a reading element that has a specific reading_type
    $service = Service::factory()->create();
    $readingElement = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
        'reading_type' => ReadingType::WORSHIP_CALL,
    ]);

    // Test the reading element component
    $component = Livewire::test('elements.reading', ['element' => $readingElement]);

    // The computed readings property should only return readings that match the reading_type
    $readings = $component->readings;

    expect($readings)->toHaveCount(1);
    expect($readings->first()->id)->toBe($callToWorshipReading->id);
    expect($readings->first()->title)->toBe('Call to Worship Reading');
});

it('returns all readings when element has no reading_type specified', function () {
    // Create readings of different types
    Reading::factory()->create(['type' => ReadingType::WORSHIP_CALL]);
    Reading::factory()->create(['type' => ReadingType::LAW]);
    Reading::factory()->create(['type' => ReadingType::CREED]);

    // Create a service with a reading element that has no reading_type
    $service = Service::factory()->create();
    $readingElement = LiturgyElement::factory()->create([
        'liturgy_type' => Service::class,
        'liturgy_id' => $service->id,
        'type' => LiturgyElementType::READING,
        'reading_type' => null,
    ]);

    // Test the reading element component
    $component = Livewire::test('elements.reading', ['element' => $readingElement]);

    // The computed readings property should return all readings
    $readings = $component->readings;

    expect($readings)->toHaveCount(3);
});
