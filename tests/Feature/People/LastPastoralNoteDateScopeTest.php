<?php

use App\Models\PastoralNote;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('last_noted_at equals the newest note created_at when person has several notes', function (): void {
    $person = Person::factory()->create();

    PastoralNote::factory()->create([
        'person_id' => $person->id,
        'created_at' => now()->subDays(10),
    ]);
    $newest = PastoralNote::factory()->create([
        'person_id' => $person->id,
        'created_at' => now()->subDay(),
    ]);
    PastoralNote::factory()->create([
        'person_id' => $person->id,
        'created_at' => now()->subDays(5),
    ]);

    $result = Person::query()->withLastPastoralNoteDate()->find($person->id);

    expect($result->last_noted_at->toDateTimeString())->toBe($newest->created_at->toDateTimeString());
});

test('last_noted_at is null when person has no notes', function (): void {
    $person = Person::factory()->create();

    $result = Person::query()->withLastPastoralNoteDate()->find($person->id);

    expect($result->last_noted_at)->toBeNull();
});

test('last_noted_at is cast to a Carbon datetime instance', function (): void {
    $person = Person::factory()->create();
    PastoralNote::factory()->create(['person_id' => $person->id]);

    $result = Person::query()->withLastPastoralNoteDate()->find($person->id);

    expect($result->last_noted_at)->toBeInstanceOf(Carbon::class);
});
