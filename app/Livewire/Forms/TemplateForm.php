<?php

namespace App\Livewire\Forms;

use App\Models\Template;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TemplateForm extends Form
{
    public ?Template $template = null;

    #[Validate]
    public string $name;

    #[Validate]
    public bool $default = false;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'default' => 'required|bool',
        ];
    }

    public function setTemplate(Template $template): void
    {
        $this->template = $template;

        $this->name = $template->name;
        $this->default = $template->default;
    }

    public function store(): void
    {
        $this->validate();

        Template::create($this->only(['name', 'default']));
    }

    public function update(): void
    {
        $this->validate();

        $this->template->update(
            $this->only(['name', 'default'])
        );
    }
}
