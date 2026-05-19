<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\NotificationPreference;
use App\Models\User;
use Carbon\CarbonImmutable;

class NotificationPreferenceService
{
    /**
     * Channels that quiet hours can suppress.
     *
     * @var array<int, NotificationChannel>
     */
    private const QUIET_HOURS_SUPPRESSED = [
        NotificationChannel::WEBPUSH,
        NotificationChannel::BROADCAST,
    ];

    /**
     * Filtered channel list for a notification at send time.
     *
     * @param  array<int, NotificationChannel>  $defaults
     * @return array<int, string>
     */
    public function channelsFor(
        User $user,
        NotificationEventType $event,
        array $defaults,
        ?CarbonImmutable $at = null,
    ): array {
        $disabled = $user->notificationPreferences()
            ->where('event_type', $event->value)
            ->where('enabled', false)
            ->get(['channel'])
            ->map(fn (NotificationPreference $row): NotificationChannel => $row->channel)
            ->all();

        $suppressQuietHours = ! $event->isMention() && $this->isInQuietHours($user, $at);

        return array_values(array_filter(
            array_map(
                fn (NotificationChannel $channel): ?string => match (true) {
                    in_array($channel, $disabled, true) => null,
                    $suppressQuietHours && in_array($channel, self::QUIET_HOURS_SUPPRESSED, true) => null,
                    default => $channel->value,
                },
                $defaults,
            ),
        ));
    }

    /**
     * Full preference matrix for the settings UI. Defaults to all-on; rows in the
     * database flip cells off.
     *
     * @return array<string, array<string, bool>>
     */
    public function matrixFor(User $user): array
    {
        $disabled = $user->notificationPreferences()
            ->where('enabled', false)
            ->get(['event_type', 'channel'])
            ->map(fn (NotificationPreference $row): string => $row->event_type->value.'|'.$row->channel->value)
            ->all();

        $matrix = [];

        foreach (NotificationEventType::userConfigurable() as $event) {
            $matrix[$event->value] = [];

            foreach (NotificationChannel::cases() as $channel) {
                $matrix[$event->value][$channel->value] = ! in_array(
                    $event->value.'|'.$channel->value,
                    $disabled,
                    true,
                );
            }
        }

        return $matrix;
    }

    /**
     * Persist a single cell. Toggling to default (true) deletes the row;
     * toggling off upserts a disabled row.
     */
    public function setChannel(
        User $user,
        NotificationEventType $event,
        NotificationChannel $channel,
        bool $enabled,
    ): void {
        if ($enabled) {
            $user->notificationPreferences()
                ->where('event_type', $event->value)
                ->where('channel', $channel->value)
                ->delete();

            return;
        }

        NotificationPreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'event_type' => $event->value,
                'channel' => $channel->value,
            ],
            ['enabled' => false],
        );
    }

    public function isInQuietHours(User $user, ?CarbonImmutable $at = null): bool
    {
        $start = $this->minutesFromMidnight($user->quiet_hours_start);
        $end = $this->minutesFromMidnight($user->quiet_hours_end);

        if ($start === null || $end === null || $start === $end) {
            return false;
        }

        $timezone = $user->timezone ?: config('app.timezone');
        $now = ($at ?? CarbonImmutable::now())->setTimezone($timezone);
        $nowMinutes = $now->hour * 60 + $now->minute;

        if ($start < $end) {
            return $nowMinutes >= $start && $nowMinutes < $end;
        }

        return $nowMinutes >= $start || $nowMinutes < $end;
    }

    private function minutesFromMidnight(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})/', (string) $value, $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return $hours * 60 + $minutes;
    }
}
