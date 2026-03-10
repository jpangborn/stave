<?php

namespace App\Enums;

enum GroupMessaging: string
{
    case OFF = 'off';
    case ALL_MEMBERS = 'all-members';
    case ONLY_LEADERS = 'only-leaders';

    public function label(): string
    {
        return match ($this) {
            self::OFF => 'Off',
            self::ALL_MEMBERS => 'All Members',
            self::ONLY_LEADERS => 'Only Leaders',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OFF => 'zinc',
            self::ALL_MEMBERS => 'sky',
            self::ONLY_LEADERS => 'purple',
        };
    }
}
