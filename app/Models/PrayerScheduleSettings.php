<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\PrayerScheduleGrouping;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $cycle_weeks
 * @property PrayerScheduleGrouping $group_by
 * @property array<int, string> $include_statuses
 * @property Carbon $anchor_date
 */
#[Fillable([
    'cycle_weeks',
    'group_by',
    'include_statuses',
    'anchor_date',
])]
class PrayerScheduleSettings extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cycle_weeks' => 'integer',
            'group_by' => PrayerScheduleGrouping::class,
            'include_statuses' => 'array',
            'anchor_date' => 'date',
        ];
    }

    /**
     * The single application-wide settings row, created with defaults if missing.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'cycle_weeks' => 8,
            'group_by' => PrayerScheduleGrouping::ALPHA,
            'include_statuses' => [
                MembershipStatus::MEMBER->value,
                MembershipStatus::CATECHUMEN->value,
            ],
            'anchor_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
        ]);
    }
}
