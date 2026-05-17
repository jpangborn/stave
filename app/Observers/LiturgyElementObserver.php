<?php

namespace App\Observers;

use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\User;
use App\Services\ServiceCommentSubscriptionService;
use App\Support\SectionTone;

class LiturgyElementObserver
{
    public function __construct(
        private ServiceCommentSubscriptionService $subscriptionService
    ) {}

    /**
     * Handle the LiturgyElement "creating" event.
     *
     * Seed a tonal color for sections so element rows can inherit it.
     * The color persists across renames; only the initial value is auto-picked.
     */
    public function creating(LiturgyElement $element): void
    {
        if ($element->type === LiturgyElementType::SECTION && $element->section_color === null) {
            $element->section_color = SectionTone::pick($element->name ?? '');
        }
    }

    /**
     * Handle the LiturgyElement "created" event.
     */
    public function created(LiturgyElement $element): void
    {
        $this->handleAssigneeSubscription($element);
    }

    /**
     * Handle the LiturgyElement "updated" event.
     */
    public function updated(LiturgyElement $element): void
    {
        if (! $element->wasChanged('assignee_id')) {
            return;
        }

        $service = $this->getServiceFromElement($element);
        if (! $service) {
            return;
        }

        $oldAssigneeId = $element->getOriginal('assignee_id');
        if ($oldAssigneeId) {
            $oldAssignee = User::find($oldAssigneeId);
            if ($oldAssignee) {
                $this->subscriptionService->unsubscribeUserIfNoOtherAssignments(
                    $oldAssignee,
                    $service
                );
            }
        }

        $this->handleAssigneeSubscription($element);
    }

    /**
     * Handle the LiturgyElement "deleted" event.
     */
    public function deleted(LiturgyElement $element): void
    {
        $service = $this->getServiceFromElement($element);
        if (! $service || ! $element->assignee_id) {
            return;
        }

        $assignee = $element->assignee;
        if ($assignee) {
            $this->subscriptionService->unsubscribeUserIfNoOtherAssignments(
                $assignee,
                $service
            );
        }
    }

    private function handleAssigneeSubscription(LiturgyElement $element): void
    {
        $service = $this->getServiceFromElement($element);
        if (! $service || ! $element->assignee_id) {
            return;
        }

        $assignee = $element->assignee;
        if ($assignee) {
            $this->subscriptionService->subscribeUser($assignee, $service);
        }
    }

    private function getServiceFromElement(LiturgyElement $element): ?Service
    {
        if ($element->liturgy_type !== Service::class) {
            return null;
        }

        /** @var Service|null */
        return $element->liturgy;
    }
}
