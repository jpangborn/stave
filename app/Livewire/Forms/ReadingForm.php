<?php

namespace App\Livewire\Forms;

use App\Models\Reading;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ReadingForm extends Form
{
    public ?Reading $reading;

    #[Validate]
    public string $title;

    #[Validate]
    public string $type = '';

    public ?string $text = null;

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|string',
        ];
    }

    public function setReading(Reading $reading)
    {
        $this->reading = $reading;

        $this->title = $reading->title;
        $this->type = $reading->type?->value ?? '';
        $this->text = $reading->text;
    }

    public function store()
    {
        $this->validate();

        Reading::create($this->only(['title', 'type', 'text']));
    }

    public function update()
    {
        $this->validate();

        $this->reading->update(
            $this->only(['title', 'type', 'text'])
        );
    }
}
