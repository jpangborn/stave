<?php

declare(strict_types=1);

namespace App\Enums;

enum DigestFrequency: string
{
    case OFF = 'off';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::OFF => 'Off',
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
        };
    }
}
