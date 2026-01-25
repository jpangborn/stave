<?php

use Flux\Flux;
use Livewire\Component;
use App\Livewire\Forms\SeriesForm;

new class extends Component {
    public SeriesForm $form;

    public function save()
    {
        $this->form->store();
        Flux::toast(variant: "success", text: "Series added.");
        return $this->redirect("/series", navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Add a Series</flux:heading>
    <flux:subheading size="lg" class="mb-6">Create a new series to group readings together.</flux:subheading>

    <form wire:submit="save">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mt-8">
            <div class="w-80">
                <flux:heading size="lg">Series Details</flux:heading>
                <flux:subheading>Information about the series.</flux:subheading>
            </div>

            <div class="flex-1 max-w-md space-y-6">
                <flux:field>
                    <flux:label badge="Required">Name</flux:label>
                    <flux:input type="text" name="name" wire:model="form.name" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:editor label="Description" wire:model="form.description" toolbar="heading | bold italic underline ~ undo redo" class="**:data-[slot=content]:min-h-[200px]" />

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </div>
    </form>
</section>
