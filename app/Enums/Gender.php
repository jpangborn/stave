<?php

namespace App\Enums;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FEMALE => 'pink',
            self::MALE => 'blue',
        };
    }
}
