<?php

namespace App\Enums;

enum HouseholdRole: string
{
    case HEAD_OF_HOUSEHOLD = 'head_of_household';
    case SPOUSE = 'spouse';
    case CHILD = 'child';
    case DEPENDENT = 'dependent';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HEAD_OF_HOUSEHOLD => 'Head of household',
            self::SPOUSE => 'Spouse',
            self::CHILD => 'Child',
            self::DEPENDENT => 'Dependent',
            self::OTHER => 'Other',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::HEAD_OF_HOUSEHOLD => 1,
            self::SPOUSE => 2,
            self::DEPENDENT => 3,
            self::CHILD => 4,
            self::OTHER => 5,
        };
    }
}
