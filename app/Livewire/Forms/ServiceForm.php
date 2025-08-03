<?php

namespace App\Livewire\Forms;

use App\Models\Service;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ServiceForm extends Form
{
    public $template;

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
            "date" => "required|date",
            "title" => "nullable|string|max:255",
        ];
    }

    public function setService(Service $service): void
    {
        $this->service = $service;

        $this->date = $service->date;
        $this->title = $service->title;
        $this->template_id = $service->template->id;
    }

    public function store(): void
    {
        $this->validate();

        Service::create($this->only(["date", "title", "notes"]));
    }

    public function update(): void
    {
        $this->validate();

        $this->template->update($this->only(["date", "title", "notes"]));
    }
}
