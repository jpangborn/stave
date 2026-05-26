<?php

namespace App\Enums;

enum Office: string
{
    case ELDER = 'elder';
    case DEACON = 'deacon';
    case STAFF = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::ELDER => 'Elder',
            self::DEACON => 'Deacon',
            self::STAFF => 'Staff',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ELDER => 'amber',
            self::DEACON => 'indigo',
            self::STAFF => 'zinc',
        };
    }

    public function textColorClass(): string
    {
        return match ($this) {
            self::ELDER => 'text-amber-500',
            self::DEACON => 'text-indigo-500',
            self::STAFF => 'text-zinc-500',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ELDER => 'star',
            self::DEACON => 'hand-raised',
            self::STAFF => 'briefcase',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ELDER => 'Spiritual oversight, teaching, and pastoral care.',
            self::DEACON => 'Mercy ministry and practical service to the congregation.',
            self::STAFF => 'Paid staff member of the church.',
        };
    }
}
