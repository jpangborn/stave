<?php

namespace App\Livewire\Forms;

use App\Models\Series;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SeriesForm extends Form
{
    public ?Series $series = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $description = null;

    public function setSeries(Series $series): void
    {
        $this->series = $series;

        $this->name = $series->name;
        $this->description = $series->description;
    }

    public function store(): void
    {
        $this->validate();

        Series::create(
            $this->only(['name', 'description'])
        );
    }

    public function update(): void
    {
        $this->validate();

        $this->series->update(
            $this->only(['name', 'description'])
        );
    }
}
