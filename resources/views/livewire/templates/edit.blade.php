<?php

use Flux\Flux;
use App\Models\Template;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use App\Livewire\Forms\TemplateForm;

new class extends Component {
    public TemplateForm $form;

    #[Url]
    public $tab = "details";

    public function mount(Template $template)
    {
        $this->form->setTemplate($template);
    }

    public function save()
    {
        $this->form->update();

        Flux::toast("Template updated.");
        return $this->redirect("/templates", navigate: true);
    }

    public function addElement(string $element)
    {
        $this->form->addElement($element);
    }

    public function delete()
    {
        $this->form->template->delete();

        Flux::toast("Template deleted.");
        return $this->redirect("/templates", navigate: true);
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
                        <flux:input type="text" name="name" wire:model="form.name" />
                        <flux:error name="form.name" />
                    </flux:field>

                    <flux:checkbox wire:model="form.default" label="Default Template" />

                    <div class="flex space-x-2">
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </div>
            </form>
        </flux:tab.panel>

        <flux:tab.panel name="elements" class="space-y-4">
            <div class="flex items-center gap-x-2">
                <flux:spacer />

                <flux:dropdown>
                    <flux:button variant="primary"  size="sm" icon-trailing="chevron-down">Add Element</flux:button>

                    <flux:menu>
                        @foreach(App\Enums\LiturgyElementType::cases() as $element)
                            <flux:menu.item icon="{{ $element->icon() }}" wire:click="addElement('{{ $element->value }}')">{{ $element->label() }}</flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            </div>

            <livewire:templates.elements :template="$form->template" />
        </flux:tab.panel>
    </flux:tab.group>
</section>
