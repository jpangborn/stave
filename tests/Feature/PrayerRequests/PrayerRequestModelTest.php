<?php

use App\Enums\PrayerRequestVisibility;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a person has many prayer requests', function (): void {
    $person = Person::factory()->create();
    PrayerRequest::factory()->count(3)->for($person)->create();
    PrayerRequest::factory()->create();

    expect($person->prayerRequests)->toHaveCount(3);
});

test('open scope returns only requests without a completion date', function (): void {
    PrayerRequest::factory()->count(2)->open()->create();
    PrayerRequest::factory()->completed()->create();

    expect(PrayerRequest::open()->count())->toBe(2);
});

test('completed scope returns only requests with a completion date', function (): void {
    PrayerRequest::factory()->count(2)->open()->create();
    PrayerRequest::factory()->completed()->create();

    expect(PrayerRequest::completed()->count())->toBe(1);
});

test('bulletin scope returns only bulletin-visibility requests', function (): void {
    PrayerRequest::factory()->count(2)->bulletin()->create();
    PrayerRequest::factory()->private()->create();

    expect(PrayerRequest::bulletin()->count())->toBe(2);
});

test('visibility is cast to the enum and completed_at to a datetime', function (): void {
    $request = PrayerRequest::factory()->private()->completed('2026-06-10 09:00:00')->create();

    $request->refresh();

    expect($request->visibility)->toBe(PrayerRequestVisibility::PRIVATE)
        ->and($request->completed_at)->not->toBeNull()
        ->and($request->isOpen())->toBeFalse();
});

test('open requests count can be eager loaded on a person', function (): void {
    $person = Person::factory()->create();
    PrayerRequest::factory()->count(2)->open()->for($person)->create();
    PrayerRequest::factory()->completed()->for($person)->create();

    $loaded = Person::query()
        ->withCount(['prayerRequests as open_prayer_requests_count' => fn ($query) => $query->whereNull('completed_at')])
        ->find($person->id);

    expect($loaded->open_prayer_requests_count)->toBe(2);
});

test('a prayer request tracks the user who created it', function (): void {
    $user = User::factory()->create();
    $request = PrayerRequest::factory()->state(['created_by_user_id' => $user->id])->create();

    expect($request->createdBy->is($user))->toBeTrue();
});
