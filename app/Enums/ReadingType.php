<?php

namespace App\Enums;

enum ReadingType: string
{
    case WORSHIP_CALL = 'call-to-worship';
    case LAW = 'law';
    case CONFESS_SIN = 'confession-of-sin';
    case ASSURANCE = 'assurance-of-pardon';
    case CREED = 'creed';
    case CONFESSION = 'confession';
    case CATECHISM = 'catechism';
    case PRAYER = 'prayer';
    case PRAISE = 'praise';
    case BENEDICTION = 'benediction';

    public function label(): string
    {
        return match ($this) {
            self::WORSHIP_CALL => 'Call to Worship',
            self::LAW => 'Reading of the Law',
            self::CONFESS_SIN => 'Confession of Sin',
            self::ASSURANCE => 'Assurance of Pardon',
            self::CREED => 'Creed',
            self::CONFESSION => 'Confession',
            self::CATECHISM => 'Catechism',
            self::PRAYER => 'Prayer',
            self::PRAISE => 'Praise',
            self::BENEDICTION => 'Benediction',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WORSHIP_CALL => 'sky',
            self::LAW => 'purple',
            self::CONFESS_SIN => 'green',
            self::ASSURANCE => 'yellow',
            self::CREED => 'red',
            self::CONFESSION => 'orange',
            self::CATECHISM => 'teal',
            self::PRAISE => 'cyan',
            self::PRAYER => 'indigo',
            self::BENEDICTION => 'amber',
        };
    }
}
