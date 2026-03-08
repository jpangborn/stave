<?php

namespace App\Services;

use App\Models\Service;
use App\Models\User;
use Spatie\Comments\Enums\NotificationSubscriptionType;
use Spatie\Comments\Models\CommentNotificationSubscription;

class ServiceCommentSubscriptionService
{
    /**
     * Subscribe a user to service comment notifications.
     */
    public function subscribeUser(User $user, Service $service): void
    {
        $user->subscribeToCommentNotifications(
            $service,
            NotificationSubscriptionType::All
        );
    }

    /**
     * Unsubscribe a user from service comment notifications if they have no other assignments.
     */
    public function unsubscribeUserIfNoOtherAssignments(User $user, Service $service): void
    {
        $hasOtherAssignments = $service->liturgyElements()
            ->where('assignee_id', $user->id)
            ->exists();

        if (! $hasOtherAssignments) {
            CommentNotificationSubscription::query()
                ->where('subscriber_type', $user->getMorphClass())
                ->where('subscriber_id', $user->getKey())
                ->where('commentable_type', $service->getMorphClass())
                ->where('commentable_id', $service->getKey())
                ->delete();
        }
    }

    /**
     * Sync all assignee subscriptions for a service.
     */
    public function syncServiceSubscriptions(Service $service): void
    {
        foreach ($service->assignedUsers() as $user) {
            $this->subscribeUser($user, $service);
        }
    }
}
