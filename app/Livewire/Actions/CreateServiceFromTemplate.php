<?php

namespace App\Livewire\Actions;

use App\Models\Service;
use App\Models\Template;
use App\Services\ServiceCommentSubscriptionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateServiceFromTemplate
{
    public function __construct(
        private ServiceCommentSubscriptionService $subscriptionService
    ) {}

    public function __invoke(Template $template, Carbon $date): void
    {
        $template->loadMissing(['liturgyElements', 'liturgyElements.content']);

        $service = DB::transaction(function () use ($template, $date): Service {
            $service = Service::create([
                'title' => $template->name.' – '.$date->toFormattedDateString(),
                'date' => $date,
                'template_id' => $template->id,
            ]);

            foreach ($template->liturgyElements as $element) {
                $service->liturgyElements()->create([
                    'type' => $element->type,
                    'reading_type' => $element->reading_type,
                    'order' => $element->order,
                    'name' => $element->name,
                    'description' => $element->description,
                    'assignee_id' => $element->assignee_id,
                    'content_type' => $element->content_type,
                    'content_id' => $element->content_id,
                ]);
            }

            return $service;
        });

        $this->subscriptionService->syncServiceSubscriptions($service);
    }
}
