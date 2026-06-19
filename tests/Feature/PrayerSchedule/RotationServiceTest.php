<?php

use App\Enums\HouseholdRole;
use App\Enums\MembershipStatus;
use App\Enums\PrayerScheduleGrouping;
use App\Models\Household;
use App\Models\Person;
use App\Models\PrayerScheduleSettings;
use App\Services\PrayerScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/**
 * Map each person id to the week index it was placed in.
 *
 * @param  Collection<int, Collection<int, Person>>  $buckets
 * @return array<int, int>
 */
function personWeekMap(Collection $buckets): array
{
    $map = [];
    $buckets->each(function (Collection $week, int $index) use (&$map): void {
        $week->each(function (Person $person) use (&$map, $index): void {
            $map[$person->id] = $index;
        });
    });

    return $map;
}

function buildCongregation(): void
{
    // Three multi-person households plus a couple of lone people, all members.
    foreach (['Aldridge', 'Bellamy', 'Castellano'] as $surname) {
        $household = Household::factory()->create(['name' => "{$surname} Household"]);
        Person::factory()
            ->count(3)
            ->member()
            ->inHousehold($household, HouseholdRole::OTHER)
            ->create(['last_name' => $surname]);
    }

    Person::factory()->member()->create(['last_name' => 'Devereux', 'household_id' => null]);
    Person::factory()->member()->create(['last_name' => 'Everett', 'household_id' => null]);
}

function settings(int $weeks, PrayerScheduleGrouping $groupBy): PrayerScheduleSettings
{
    $settings = PrayerScheduleSettings::current();
    $settings->update([
        'cycle_weeks' => $weeks,
        'group_by' => $groupBy,
        'include_statuses' => [MembershipStatus::MEMBER->value, MembershipStatus::CATECHUMEN->value],
        'anchor_date' => '2026-01-05', // a Monday
    ]);

    return $settings->refresh();
}

test('the rotation is deterministic across calls', function (): void {
    buildCongregation();
    $service = new PrayerScheduleService();
    $settings = settings(4, PrayerScheduleGrouping::ALPHA);

    $first = $service->buckets($settings)->map(fn (Collection $w) => $w->pluck('id')->all())->all();
    $second = $service->buckets($settings)->map(fn (Collection $w) => $w->pluck('id')->all())->all();

    expect($first)->toBe($second);
});

test('no household is split across weeks in alphabetical mode', function (): void {
    buildCongregation();
    $service = new PrayerScheduleService();
    $buckets = $service->buckets(settings(4, PrayerScheduleGrouping::ALPHA));

    assertHouseholdsIntact($buckets);
});

test('no household is split across weeks in by-household mode', function (): void {
    buildCongregation();
    $service = new PrayerScheduleService();
    $buckets = $service->buckets(settings(4, PrayerScheduleGrouping::HOUSEHOLD));

    assertHouseholdsIntact($buckets);
});

function assertHouseholdsIntact(Collection $buckets): void
{
    $weekFor = personWeekMap($buckets);

    Household::query()->with('people')->get()->each(function (Household $household) use ($weekFor): void {
        $weeks = $household->people->pluck('id')->map(fn (int $id): int => $weekFor[$id])->unique();
        expect($weeks)->toHaveCount(1, "Household {$household->name} was split across weeks");
    });
}

test('alphabetical mode lays households out in contiguous surname order', function (): void {
    buildCongregation();
    $service = new PrayerScheduleService();
    $buckets = $service->buckets(settings(4, PrayerScheduleGrouping::ALPHA));

    $orderedSurnames = $buckets
        ->flatMap(fn (Collection $week) => $week->pluck('last_name'))
        ->values();

    $sorted = $orderedSurnames->sort()->values();

    expect($orderedSurnames->all())->toBe($sorted->all());
});

test('by-household mode balances per-week headcounts', function (): void {
    buildCongregation();
    $service = new PrayerScheduleService();
    $buckets = $service->buckets(settings(4, PrayerScheduleGrouping::HOUSEHOLD));

    $counts = $buckets->map(fn (Collection $week): int => $week->count());

    // 11 people across 4 weeks: the spread should stay within one household's size.
    expect($counts->max() - $counts->min())->toBeLessThanOrEqual(3)
        ->and($counts->sum())->toBe(11);
});

test('terminated people are never scheduled', function (): void {
    buildCongregation();
    Person::factory()->create(['membership_status' => MembershipStatus::TERMINATED, 'last_name' => 'Zephyr']);

    $service = new PrayerScheduleService();
    // Even if a caller passes terminated explicitly, it is excluded.
    $buckets = $service->buckets(settings(4, PrayerScheduleGrouping::ALPHA), [
        MembershipStatus::MEMBER->value,
        MembershipStatus::TERMINATED->value,
    ]);

    $names = $buckets->flatMap(fn (Collection $week) => $week->pluck('last_name'));

    expect($names)->not->toContain('Zephyr');
});

test('status override changes who is included', function (): void {
    buildCongregation();
    Person::factory()->adherent()->create(['last_name' => 'Quint', 'household_id' => null]);

    $service = new PrayerScheduleService();
    $settings = settings(4, PrayerScheduleGrouping::ALPHA);

    $withoutAdherents = $service->buckets($settings)->flatMap(fn (Collection $w) => $w->pluck('last_name'));
    expect($withoutAdherents)->not->toContain('Quint');

    $withAdherents = $service->buckets($settings, [
        MembershipStatus::MEMBER->value,
        MembershipStatus::ADHERENT->value,
    ])->flatMap(fn (Collection $w) => $w->pluck('last_name'));
    expect($withAdherents)->toContain('Quint');
});

test('current week index advances and wraps around the cycle', function (): void {
    $settings = settings(8, PrayerScheduleGrouping::ALPHA); // anchor 2026-01-05 (Mon)
    $service = new PrayerScheduleService();

    Carbon::setTestNow('2026-01-07'); // same week as anchor
    expect($service->currentWeekIndex($settings))->toBe(0);

    Carbon::setTestNow('2026-01-26'); // 3 weeks later
    expect($service->currentWeekIndex($settings))->toBe(3);

    Carbon::setTestNow('2026-03-02'); // 8 weeks later -> wraps to 0
    expect($service->currentWeekIndex($settings))->toBe(0);

    Carbon::setTestNow('2026-03-16'); // 10 weeks later -> 2
    expect($service->currentWeekIndex($settings))->toBe(2);

    Carbon::setTestNow();
});

test('stats summarise the rotation', function (): void {
    buildCongregation();
    $service = new PrayerScheduleService();

    Carbon::setTestNow('2026-01-05');
    $stats = $service->stats(settings(4, PrayerScheduleGrouping::ALPHA));
    Carbon::setTestNow();

    expect($stats['total'])->toBe(11)
        ->and($stats['weeks'])->toBe(4)
        ->and($stats['perWeek'])->toBe(3)
        ->and($stats['currentWeekIndex'])->toBe(0);
});
