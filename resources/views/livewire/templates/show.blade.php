<?php

use App\Models\Template;
use App\Enums\Permission;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use App\Livewire\Forms\TemplateForm;

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
    <flux:heading size="xl" level="1">{{ $form->name }}</flux:heading>

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model="tab">
            <flux:tab name="details" icon="notepad-text-dashed">Details</flux:tab>
            <flux:tab name="elements" icon="list-collapse">Elements</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <form wire:submit="save" class="flex flex-col lg:flex-row gap-4 lg:gap-6">
                <div class="w-80">
                    <flux:heading size="lg">Reading Details</flux:heading>
                    <flux:subheading>Information about the reading.</flux:subheading>
                </div>

                <div class="flex-1 max-w-md space-y-6">
                    <flux:field>
                        <flux:label badge="Required">Name</flux:label>
                        <flux:input type="text" name="name" wire:model="form.name" variant="filled" readonly copyable />
                        <flux:error name="form.name" />
                    </flux:field>

                    <flux:checkbox wire:model="form.default" label="Default Template" readonly />
                </div>
            </form>
        </flux:tab.panel>
    </flux:tab.group>
</section>
