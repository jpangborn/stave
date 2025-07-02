<?php

namespace App\Enums;

enum LiturgyElementType: string
{
    case SECTION = 'section';
    case SONG = 'song';
    case READING = 'reading';
    case SERMON = 'sermon';
    case PRAYER = 'prayer';
    case SUPPER = 'supper';
    case BAPTISM = 'baptism';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SECTION => 'Section',
            self::SONG => 'Song',
            self::READING => 'Reading',
            self::SERMON => 'Sermon',
            self::PRAYER => 'Prayer',
            self::SUPPER => 'Lord\'s Supper',
            self::BAPTISM => 'Baptism',
            self::OTHER => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SECTION => 'heading',
            self::SONG => 'musical-note',
            self::READING => 'book-open-text',
            self::SERMON => 'lectern',
            self::PRAYER => 'message-circle-dashed',
            self::SUPPER => 'hand-platter',
            self::BAPTISM => 'waves',
            self::OTHER => 'clipboard',
        };
    }
}
