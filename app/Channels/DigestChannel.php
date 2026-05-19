<?php

declare(strict_types=1);

namespace App\Channels;

use App\Enums\DigestFrequency;
use App\Models\EmailDigestItem;
use App\Models\User;
use Illuminate\Notifications\Notification;

class DigestChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        if ($notifiable->digest_frequency === DigestFrequency::OFF) {
            return;
        }

        if (! method_exists($notification, 'eventType') || ! method_exists($notification, 'toArray')) {
            return;
        }

        EmailDigestItem::create([
            'user_id' => $notifiable->id,
            'event_type' => $notification->eventType()->value,
            'data' => $notification->toArray($notifiable),
        ]);
    }
}
