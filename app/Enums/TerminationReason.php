<?php

namespace App\Enums;

enum TerminationReason: string
{
    case DECEASED = 'deceased';
    case TRANSFERRED = 'transferred';
    case EXCOMMUNICATED = 'excommunicated';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DECEASED => 'Deceased',
            self::TRANSFERRED => 'Transferred',
            self::EXCOMMUNICATED => 'Excommunicated',
            self::OTHER => 'Other',
        };
    }
}
