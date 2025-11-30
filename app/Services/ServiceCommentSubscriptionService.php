<?php

namespace App\Services;

use App\Models\Service;
use App\Models\User;
use Spatie\Comments\Enums\NotificationSubscriptionType;

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
            $user->unsubscribeFromCommentNotifications($service);
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
