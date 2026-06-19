<?php

namespace App\Enums;

enum PrayerRequestVisibility: string
{
    case BULLETIN = 'bulletin';
    case PRIVATE = 'private';

    public function label(): string
    {
        return match ($this) {
            self::BULLETIN => 'Bulletin',
            self::PRIVATE => 'Private',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BULLETIN => 'emerald',
            self::PRIVATE => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BULLETIN => 'megaphone',
            self::PRIVATE => 'lock-closed',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BULLETIN => 'Shared in the Sunday bulletin and the Monday elders\' email.',
            self::PRIVATE => 'Confidential — never included in any email.',
        };
    }
}
