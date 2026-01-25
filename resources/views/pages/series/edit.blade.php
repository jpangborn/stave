<?php

use App\Models\Series;
use Livewire\Component;
use App\Livewire\Forms\SeriesForm;
use Flux\Flux;

new class extends Component {
    public SeriesForm $form;

    public function mount(Series $series): void
    {
        $this->form->setSeries($series);
    }

    public function save()
    {
        $this->form->update();

        Flux::toast("Series updated.");
        return $this->redirect("/series", navigate: true);
    }

    public function delete()
    {
        $this->form->series->delete();

        Flux::toast("Series deleted.");
        return $this->redirect("/series", navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit Series: {{ $form->name }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        {{ $form->series->readings->count() }} {{ Str::plural('reading', $form->series->readings->count()) }}
    </flux:subheading>

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

                <div class="flex space-x-2">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                    <flux:modal.trigger name="delete-series">
                        <flux:button variant="danger">Delete</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
    </form>

    <flux:modal name="delete-series" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete series?</flux:heading>

                <flux:subheading>
                    <p>This will permanently delete the series.</p>
                    <p>Readings in this series will not be deleted, but will be removed from the series.</p>
                    <p>This cannot be undone.</p>
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="button" wire:click="delete" variant="danger">Delete series</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
