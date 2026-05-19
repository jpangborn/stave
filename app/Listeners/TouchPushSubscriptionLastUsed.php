<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Update the `last_used_at` timestamp on a user's push subscriptions whenever a
 * web-push notification is successfully sent. We touch all of the notifiable's
 * subscriptions in one query rather than tracking which specific endpoint
 * succeeded — accurate enough for a "last seen" indicator, much simpler than
 * patching the vendor channel.
 */
class TouchPushSubscriptionLastUsed
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== WebPushChannel::class) {
            return;
        }

        $notifiable = $event->notifiable;

        if (! $notifiable instanceof Model) {
            return;
        }

        PushSubscription::query()
            ->where('subscribable_type', $notifiable::class)
            ->where('subscribable_id', $notifiable->getKey())
            ->update(['last_used_at' => now()]);
    }
}
