<?php

namespace App\Enums;

enum PrayerScheduleGrouping: string
{
    case ALPHA = 'alpha';
    case HOUSEHOLD = 'household';

    public function label(): string
    {
        return match ($this) {
            self::ALPHA => 'Alphabetical',
            self::HOUSEHOLD => 'By household',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ALPHA => 'bars-arrow-down',
            self::HOUSEHOLD => 'home-modern',
        };
    }
}
