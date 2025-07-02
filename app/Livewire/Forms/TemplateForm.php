<?php

namespace App\Livewire\Forms;

use App\Models\Template;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TemplateForm extends Form
{
    public ?Template $template;

    #[Validate]
    public string $name;

    #[Validate]
    public bool $default = false;

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'default' => 'required|bool',
        ];
    }

    public function setTemplate(Template $template)
    {
        $this->template = $template;

        $this->name = $template->name;
        $this->default = $template->default;
    }

    public function store()
    {
        $this->validate();

        Template::create($this->only(['name', 'default']));
    }

    public function update()
    {
        $this->validate();

        $this->template->update(
            $this->only(['name', 'default'])
        );
    }
}
