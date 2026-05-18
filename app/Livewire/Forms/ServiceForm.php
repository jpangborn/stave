<?php

namespace App\Livewire\Forms;

use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ServiceForm extends Form
{
    public ?Service $service = null;

    #[Validate]
    public Carbon $date;

    #[Validate]
    public ?string $title = null;

    public ?int $template_id = null;

    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'title' => 'nullable|string|max:255',
        ];
    }

    public function setService(Service $service): void
    {
        $this->service = $service;

        $this->date = $service->date;
        $this->title = $service->title;
        $this->template_id = $service->template?->id;
    }

    public function store(): void
    {
        $this->validate();

        Service::create($this->only(['date', 'title']));
    }

    public function update(): void
    {
        $this->validate();

        $this->service?->update($this->only(['date', 'title']));
    }

    /**
     * Persist a single attribute (used by inline edits on the show page).
     */
    public function saveTitle(): void
    {
        $this->validateOnly('title');

        $this->service?->update(['title' => $this->title]);
    }

    /**
     * Clone this service and all of its liturgy elements. The new service
     * keeps the same template + the same date, with " (Copy)" appended to
     * the title.
     */
    public function duplicate(): Service
    {
        abort_if($this->service === null, 404);

        return DB::transaction(function () {
            $clone = Service::create([
                'title' => trim(($this->service->title ?? 'Untitled').' (Copy)'),
                'date' => $this->service->date,
                'template_id' => $this->service->template_id,
                'notes' => $this->service->notes,
            ]);

            foreach ($this->service->liturgyElements as $element) {
                $copy = $element->replicate(['liturgy_type', 'liturgy_id']);
                $copy->liturgy()->associate($clone);
                $copy->save();
            }

            return $clone;
        });
    }
}
