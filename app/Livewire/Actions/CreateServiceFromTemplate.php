<?php

namespace App\Livewire\Actions;

use App\Models\Service;
use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateServiceFromTemplate extends Component
{
    public function __invoke(Template $template, string $date)
    {
        $date = Carbon::parse($date);

        DB::transaction(function () use ($template, $date) {
            $service = Service::create([
                'title' => $template->name.' – '.$date->toFormattedDateString(),
                'date' => $date,
                'template_id' => $template->id,
            ]);

            foreach ($template->liturgyElements as $element) {
                $service->liturgyElements()->create([
                    'type' => $element->type,
                    'order' => $element->order,
                    'assigned_to' => $element->assigned_to,
                    'content_type' => $element->content_type,
                    'content_id' => $element->content_id,
                ]);
            }
        });

        return redirect()
            ->route('services.index')
            ->with('success', 'Service created successfully from template.');
    }
}
