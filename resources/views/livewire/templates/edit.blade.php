<?php

use App\Livewire\Forms\LiturgyElementForm;
use Flux\Flux;
use App\Models\Template;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use App\Livewire\Forms\TemplateForm;

new class extends Component {
    public TemplateForm $form;
    public LiturgyElementForm $elementForm;

    #[Url]
    public $tab = "details";

    public function mount(Template $template): void
    {
        $this->form->setTemplate($template);
    }

    #[Computed]
    public function users()
    {
        return User::all();
    }

    #[On("related-model-added")]
    public function refreshTemplate(): void
    {
        $this->form->setTemplate(
            $this->form->template->fresh(["liturgyElements"]),
        );
    }

    public function save()
    {
        $this->form->update();

        Flux::toast("Template updated.");
        return $this->redirect("/templates", navigate: true);
    }

    public function addElement(string $element): void
    {
        $this->elementForm->type = $element;

        Flux::modal("add-element")->show();
    }

    public function saveElement(): void
    {
        $elements = $this->form->template->liturgyElements();

        $this->elementForm->order =
            $elements->count() === 0 ? 0 : $elements->max("order")->increment();

        $this->elementForm->parent = $this->form->template;

        $this->elementForm->store();
        $this->reset("elementForm");
        $this->dispatch("related-model-added");
        Flux::modal("add-element")->close();
        Flux::toast(variant: "success", text: "Element added to template.");
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
    @if($this->form->default)
    <flux:subheading><flux:badge color="green">Default</flux:badge></flux:subheading>
    @endif

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model="tab">
            <flux:tab name="details" icon="notepad-text-dashed">Details</flux:tab>
            <flux:tab name="elements" icon="list-collapse">Elements</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <form wire:submit="save" class="flex flex-col lg:flex-row gap-4 lg:gap-6">
                <div class="w-80">
                    <flux:heading size="lg">Template Details</flux:heading>
                    <flux:subheading>Information about the template.</flux:subheading>
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

            <livewire:templates.elements :template-id="$form->template->id" />
        </flux:tab.panel>
    </flux:tab.group>

    <flux:modal variant="flyout" name="add-element">
        <form wire:submit="saveElement" class="space-y-6">
            <div>
                <flux:heading size="lg">Add Liturgy Element</flux:heading>
            </div>

            <flux:select label="Type" variant="listbox" wire:model="elementForm.type">
                @foreach(App\Enums\LiturgyElementType::cases() as $element)
                    <flux:select.option value="{{ $element->value }}">
                        <div class="flex items-center gap-x-2">
                            <flux:icon name="{{ $element->icon() }}" />{{ $element->label() }}
                        </div>
                    </flux:select.option>
                @endforeach
            </flux:select>

            @if(isset($elementForm->type) && $elementForm->type === App\Enums\LiturgyElementType::READING->value)
            <flux:select label="Reading Type" variant="listbox" wire:model="elementForm.reading_type">
                @foreach(App\Enums\ReadingType::cases() as $reading_type)
                    <flux:select.option value="{{ $reading_type->value }}">{{ $reading_type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            @endif

            <flux:field>
                <flux:label for="element_name">Name</flux:label>
                <flux:input id="element_name" placeholder="Enter a name..." wire:model="elementForm.name" />
                <flux:error name="elementForm.name" />
            </flux:field>

            <flux:field>
                <flux:label for="element_description">Description</flux:label>
                <flux:input id="element_description" wire:model="elementForm.description" />
                <flux:error name="elementForm.description" />
            </flux:field>

            <flux:select label="Assignee" variant="listbox" wire:model="elementForm.assignee_id" placeholder="Select an assignee...">
                @foreach($this->users as $user)
                    <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Add Element</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
