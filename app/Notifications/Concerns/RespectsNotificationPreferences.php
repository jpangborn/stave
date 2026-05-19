<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Enums\DigestFrequency;
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

        $channels = app(NotificationPreferenceService::class)->channelsFor(
            $notifiable,
            $event,
            $event->defaultChannels(),
        );

        return $this->rewriteMailToDigest($notifiable, $event, $channels);
    }

    /**
     * Non-mention events delivered via `mail` are batched into the user's digest
     * unless the user opted out by setting `digest_frequency` to OFF.
     *
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    private function rewriteMailToDigest(User $user, NotificationEventType $event, array $channels): array
    {
        if ($event->isMention()) {
            return $channels;
        }

        if ($user->digest_frequency === DigestFrequency::OFF) {
            return $channels;
        }

        $index = array_search(NotificationChannel::MAIL->value, $channels, true);

        if ($index === false) {
            return $channels;
        }

        $channels[$index] = 'digest';

        return $channels;
    }
}
