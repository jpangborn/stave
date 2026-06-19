<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Enums\PrayerScheduleGrouping;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\PrayerScheduleSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PrayerScheduleService
{
    /**
     * The prayer rotation, as an ordered collection of `cycle_weeks` weeks.
     * Each week is a collection of People. Households are never split across
     * weeks — every member of a household shares a week.
     *
     * @param  array<int, string|MembershipStatus>|null  $statusOverride
     * @return Collection<int, Collection<int, Person>>
     */
    public function buckets(PrayerScheduleSettings $settings, ?array $statusOverride = null): Collection
    {
        $weeks = max(1, $settings->cycle_weeks);
        $units = $this->units($this->resolveStatuses($settings, $statusOverride));

        $grouping = $settings->group_by;

        /** @var array<int, array<int, Person>> $bucketArrays */
        $bucketArrays = $grouping === PrayerScheduleGrouping::HOUSEHOLD
            ? $this->packBalanced($units, $weeks)
            : $this->chunkSequential($units, $weeks);

        return collect($bucketArrays)->map(
            fn (array $people): Collection => collect($people),
        );
    }

    /**
     * The people scheduled for a single week.
     *
     * @param  array<int, string|MembershipStatus>|null  $statusOverride
     * @return Collection<int, Person>
     */
    public function peopleForWeek(PrayerScheduleSettings $settings, int $weekIndex, ?array $statusOverride = null): Collection
    {
        return $this->buckets($settings, $statusOverride)->get($weekIndex) ?? collect();
    }

    /**
     * The prayer-email payload for a week: each scheduled person with ONLY their
     * open, bulletin-visibility requests. Private requests and pastoral notes are
     * never included — this is the confidentiality boundary for the Monday email.
     *
     * @param  array<int, string|MembershipStatus>|null  $statusOverride
     * @return array<int, array{name: string, household: ?string, status: string, requests: array<int, string>}>
     */
    public function bulletinDigestForWeek(PrayerScheduleSettings $settings, int $weekIndex, ?array $statusOverride = null): array
    {
        $ids = $this->peopleForWeek($settings, $weekIndex, $statusOverride)->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        $requestsByPerson = PrayerRequest::query()
            ->open()
            ->bulletin()
            ->whereIn('person_id', $ids)
            ->orderBy('created_at')
            ->get()
            ->groupBy('person_id');

        return Person::query()
            ->whereIn('id', $ids)
            ->with('household')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Person $person): array => [
                'name' => $person->full_name,
                'household' => $person->household?->name,
                'status' => $person->membership_status->label(),
                'requests' => ($requestsByPerson->get($person->id) ?? collect())->pluck('body')->all(),
            ])
            ->all();
    }

    /**
     * The zero-based index of the current week within the cycle, derived from
     * the anchor date so it is reproducible without any stored "current week".
     */
    public function currentWeekIndex(PrayerScheduleSettings $settings, ?CarbonInterface $now = null): int
    {
        $weeks = max(1, $settings->cycle_weeks);

        $anchorMonday = $settings->anchor_date->copy()->startOfWeek(Carbon::MONDAY);
        $nowMonday = ($now ? Carbon::instance($now) : Carbon::now())->startOfWeek(Carbon::MONDAY);

        $weeksSince = (int) floor($anchorMonday->diffInDays($nowMonday, false) / 7);

        return (($weeksSince % $weeks) + $weeks) % $weeks;
    }

    /**
     * A human label for a week's calendar range relative to the current week,
     * e.g. "Jun 15 – 21".
     */
    public function weekRange(PrayerScheduleSettings $settings, int $weekIndex, ?CarbonInterface $now = null): string
    {
        $current = $this->currentWeekIndex($settings, $now);
        $reference = $now ? Carbon::instance($now) : Carbon::now();
        $monday = $reference->copy()->startOfWeek(Carbon::MONDAY)->addWeeks($weekIndex - $current);
        $sunday = $monday->copy()->addDays(6);

        $endFormat = $monday->month === $sunday->month ? 'j' : 'M j';

        return $monday->format('M j').' – '.$sunday->format($endFormat);
    }

    /**
     * Summary figures for the schedule header.
     *
     * @param  array<int, string|MembershipStatus>|null  $statusOverride
     * @return array{total: int, weeks: int, perWeek: int, currentWeekIndex: int}
     */
    public function stats(PrayerScheduleSettings $settings, ?array $statusOverride = null, ?CarbonInterface $now = null): array
    {
        $weeks = max(1, $settings->cycle_weeks);
        $total = $this->eligiblePeople($this->resolveStatuses($settings, $statusOverride))->count();

        return [
            'total' => $total,
            'weeks' => $weeks,
            'perWeek' => (int) round($total / $weeks),
            'currentWeekIndex' => $this->currentWeekIndex($settings, $now),
        ];
    }

    /**
     * Eligible people, sorted by a stable key (surname, first name, id) so the
     * rotation is deterministic across requests.
     *
     * @param  array<int, string>  $statuses
     * @return Collection<int, Person>
     */
    public function eligiblePeople(array $statuses): Collection
    {
        if ($statuses === []) {
            return collect();
        }

        return Person::query()
            ->whereIn('membership_status', $statuses)
            ->with('household')
            ->get()
            ->sortBy([
                ['last_name', 'asc'],
                ['first_name', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    /**
     * Eligible people grouped into household units (a person with no household
     * is a singleton unit), preserving surname order of each unit's first member.
     *
     * @param  array<int, string>  $statuses
     * @return Collection<int, Collection<int, Person>>
     */
    public function units(array $statuses): Collection
    {
        return $this->eligiblePeople($statuses)
            ->groupBy(fn (Person $person): string => $person->household_id !== null
                ? 'h'.$person->household_id
                : 'p'.$person->id)
            ->values();
    }

    /**
     * Alphabetical distribution: fill week 1, then week 2, … keeping each
     * household together so each week holds a contiguous run of households.
     *
     * @param  Collection<int, Collection<int, Person>>  $units
     * @return array<int, array<int, Person>>
     */
    private function chunkSequential(Collection $units, int $weeks): array
    {
        $buckets = array_fill(0, $weeks, []);
        $total = $units->sum(fn (Collection $unit): int => $unit->count());
        $target = (int) ceil($total / $weeks);

        $week = 0;
        foreach ($units as $unit) {
            $members = $unit->all();

            if ($buckets[$week] !== []
                && (count($buckets[$week]) + count($members)) > $target
                && $week < $weeks - 1) {
                $week++;
            }

            $buckets[$week] = array_merge($buckets[$week], $members);
        }

        return $buckets;
    }

    /**
     * By-household distribution: greedily assign each household to the week with
     * the smallest current headcount, evening out per-week counts.
     *
     * @param  Collection<int, Collection<int, Person>>  $units
     * @return array<int, array<int, Person>>
     */
    private function packBalanced(Collection $units, int $weeks): array
    {
        $buckets = array_fill(0, $weeks, []);

        foreach ($units as $unit) {
            $smallest = 0;
            for ($i = 1; $i < $weeks; $i++) {
                if (count($buckets[$i]) < count($buckets[$smallest])) {
                    $smallest = $i;
                }
            }

            $buckets[$smallest] = array_merge($buckets[$smallest], $unit->all());
        }

        return $buckets;
    }

    /**
     * Normalise the status set to string values, never including Terminated.
     *
     * @param  array<int, string|MembershipStatus>|null  $statusOverride
     * @return array<int, string>
     */
    private function resolveStatuses(PrayerScheduleSettings $settings, ?array $statusOverride): array
    {
        $statuses = $statusOverride ?? $settings->include_statuses;

        return collect($statuses)
            ->map(fn (string|MembershipStatus $status): string => $status instanceof MembershipStatus ? $status->value : $status)
            ->reject(fn (string $status): bool => $status === MembershipStatus::TERMINATED->value)
            ->unique()
            ->values()
            ->all();
    }
}
