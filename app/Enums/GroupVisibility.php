<?php

namespace App\Enums;

enum GroupVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PUBLIC => 'green',
            self::PRIVATE => 'amber',
        };
    }
}
