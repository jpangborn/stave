<?php

namespace App\Enums;

enum GroupRole: string
{
    case LEADER = 'leader';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::LEADER => 'Leader',
            self::MEMBER => 'Member',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LEADER => 'purple',
            self::MEMBER => 'sky',
        };
    }
}
