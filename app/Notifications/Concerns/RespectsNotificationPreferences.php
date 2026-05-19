<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\User;
use App\Services\NotificationPreferenceService;

trait RespectsNotificationPreferences
{
    abstract public function eventType(): NotificationEventType;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $event = $this->eventType();

        if (! $notifiable instanceof User) {
            return array_map(fn (NotificationChannel $channel): string => $channel->value, $event->defaultChannels());
        }

        return app(NotificationPreferenceService::class)->channelsFor(
            $notifiable,
            $event,
            $event->defaultChannels(),
        );
    }
}
