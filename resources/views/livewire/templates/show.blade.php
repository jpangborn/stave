<?php

use App\Livewire\Forms\TemplateForm;
use App\Models\Template;
use App\Enums\Permission;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component {
    public TemplateForm $form;

    #[Url]
    public $tab = "details";

    protected $listeners = [
        "refreshParent" => '$refresh',
    ];

    public function mount(Template $template)
    {
        $this->form->setTemplate($template);
    }
};
?>

<section class="w-full">
    <div class="flex items-center">
        <header>
            <flux:heading size="xl" level="1">{{ $form->name }}</flux:heading>
            @if($this->form->default)
            <flux:subheading><flux:badge color="green">Default</flux:badge></flux:subheading>
            @endif
        </header>
        <flux:spacer />
        <flux:button size="sm" variant="primary" href="{{ route('templates.edit', ['template' => $form->template]) }}">Edit</flux:button>
    </div>

    <div class="mt-6">
        <livewire:templates.elements :template-id="$form->template->id" />
    </div>
</section>
