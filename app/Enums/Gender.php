<?php

namespace App\Enums;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case PREFER_NOT_TO_SAY = 'prefer_not_to_say';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::PREFER_NOT_TO_SAY => 'Prefer not to say',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FEMALE => 'pink',
            self::MALE => 'blue',
            self::PREFER_NOT_TO_SAY => 'zinc',
        };
    }
}
