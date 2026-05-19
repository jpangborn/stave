<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case MAIL = 'mail';
    case BROADCAST = 'broadcast';
    case WEBPUSH = 'webpush';
    case DATABASE = 'database';

    public function label(): string
    {
        return match ($this) {
            self::MAIL => 'Email',
            self::BROADCAST => 'In-app',
            self::WEBPUSH => 'Push',
            self::DATABASE => 'Inbox',
        };
    }
}
