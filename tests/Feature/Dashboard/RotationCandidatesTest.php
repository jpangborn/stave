<?php

use App\Enums\AccessRole;
use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function rotationCandidatesFor(User $admin): Collection
{
    return Livewire::actingAs($admin)->test('pages::dashboard.index')->instance()->rotationCandidates;
}

test('rotationCandidates sorts never-used songs before used ones', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);

    $usedSong = Song::factory()->create();
    $service = Service::factory()->create(['date' => today()->subWeek()]);
    LiturgyElement::factory()->for($service, 'liturgy')->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $usedSong->id,
    ]);

    $neverUsedSong = Song::factory()->create();

    $ids = rotationCandidatesFor($admin)->pluck('id')->all();

    expect($ids)->toBe([$neverUsedSong->id, $usedSong->id]);
});

test('rotationCandidates orders used songs ascending by last-used date', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);

    $recentlyUsedSong = Song::factory()->create();
    $recentService = Service::factory()->create(['date' => today()->subDay()]);
    LiturgyElement::factory()->for($recentService, 'liturgy')->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $recentlyUsedSong->id,
    ]);

    $longAgoUsedSong = Song::factory()->create();
    $oldService = Service::factory()->create(['date' => today()->subMonth()]);
    LiturgyElement::factory()->for($oldService, 'liturgy')->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $longAgoUsedSong->id,
    ]);

    $ids = rotationCandidatesFor($admin)->pluck('id')->all();

    expect($ids)->toBe([$longAgoUsedSong->id, $recentlyUsedSong->id]);
});

test('rotationCandidates is limited to 5 songs', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);

    Song::factory()->count(6)->create();

    expect(rotationCandidatesFor($admin))->toHaveCount(5);
});

test('rotationCandidates treats a song used only in a future-dated service as never used', function (): void {
    $admin = User::factory()->create();
    $admin->grantAccessRole(AccessRole::LITURGY_ADMIN);

    $futureOnlySong = Song::factory()->create();
    $futureService = Service::factory()->create(['date' => today()->addWeek()]);
    LiturgyElement::factory()->for($futureService, 'liturgy')->create([
        'type' => LiturgyElementType::SONG,
        'content_type' => Song::class,
        'content_id' => $futureOnlySong->id,
    ]);

    $candidate = rotationCandidatesFor($admin)->firstWhere('id', $futureOnlySong->id);

    expect($candidate->last_used_date)->toBeNull();
});
