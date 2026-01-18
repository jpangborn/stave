<?php

use Flux\Flux;
use Livewire\Component;
use App\Livewire\Forms\TemplateForm;

new class extends Component {
    public TemplateForm $form;

    public function save()
    {
        $this->form->store();
        Flux::toast(variant: "success", text: "Tempalte added.");
        return $this->redirect("/templates", navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Add a Template</flux:heading>
    <flux:subheading size="lg" class="mb-6">Fill in details about the template.</flux:subheading>

    <form wire:submit="save">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mt-8">
            <div class="w-80">
                <flux:heading size="lg">Template Details</flux:heading>
                <flux:subheading>Information about the template.</flux:subheading>
            </div>

            <div class="flex-1 max-w-md space-y-6">
                <flux:field>
                    <flux:label badge="Required">Name</flux:label>
                    <flux:input type="text" name="name" wire:model.deep="form.name" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:checkbox wire:model.deep="form.default" label="Default Template" />

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </div>
    </form>
</section>
