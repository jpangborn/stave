<?php

namespace App\Livewire\Forms;

use App\Models\Reading;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ReadingForm extends Form
{
    public ?Reading $reading = null;

    #[Validate]
    public string $title;

    #[Validate]
    public string $type = '';

    public ?string $text = null;

    public ?int $series_id = null;

    public ?int $series_order = null;

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'series_id' => 'nullable|exists:series,id',
            'series_order' => 'nullable|required_with:series_id|integer|min:1',
        ];
    }

    public function setReading(Reading $reading): void
    {
        $this->reading = $reading;

        $this->title = $reading->title;
        $this->type = $reading->type->value; // @phpstan-ignore property.nonObject
        $this->text = $reading->text;
        $this->series_id = $reading->series_id;
        $this->series_order = $reading->series_order;
    }

    public function store(): void
    {
        $this->validate();

        Reading::create($this->only(['title', 'type', 'text', 'series_id', 'series_order']));
    }

    public function update(): void
    {
        $this->validate();

        $this->reading->update(
            $this->only(['title', 'type', 'text', 'series_id', 'series_order'])
        );
    }
}
