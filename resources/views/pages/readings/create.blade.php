<?php

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Livewire\Forms\ReadingForm;
use App\Models\Series;

new class extends Component {
    public ReadingForm $form;

    #[Computed]
    public function seriesList()
    {
        return Series::query()->orderBy('name')->get();
    }

    public function save()
    {
        $this->form->store();
        Flux::toast(variant: "success", text: "Reading added.");
        return $this->redirect("/readings", navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Add a Reading</flux:heading>
    <flux:subheading size="lg" class="mb-6">Fill in details about the reading.</flux:subheading>

    <form wire:submit="save">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mt-8">
            <div class="w-80">
                <flux:heading size="lg">Reading Details</flux:heading>
                <flux:subheading>Information about the reading.</flux:subheading>
            </div>

            <div class="flex-1 max-w-md space-y-6">
                <flux:field>
                    <flux:label badge="Required">Title</flux:label>
                    <flux:input type="text" name="title" wire:model="form.title" />
                    <flux:error name="form.title" />
                </flux:field>

                <flux:select variant="listbox" badge="Required" label="Type" wire:model="form.type" placeholder="Select the type..." required>
                    @foreach(\App\Enums\ReadingType::cases() as $readingType)
                        <flux:select.option value="{{ $readingType->value }}">{{ $readingType->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select variant="listbox" label="Series" wire:model="form.series_id" placeholder="No series" clearable>
                    @foreach($this->seriesList as $seriesItem)
                        <flux:select.option value="{{ $seriesItem->id }}">{{ $seriesItem->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if($form->series_id)
                    <flux:field>
                        <flux:label>Order in Series</flux:label>
                        <flux:input type="number" wire:model="form.series_order" min="1" />
                        <flux:description>Position of this reading in the series</flux:description>
                    </flux:field>
                @endif

                <flux:editor label="Text" wire:model="form.text" toolbar="heading | bold italic underline ~ undo redo" class="**:data-[slot=content]:min-h-[400px]" />

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </div>
    </form>
</section>
