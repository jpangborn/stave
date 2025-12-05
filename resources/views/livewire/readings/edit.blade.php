<?php

use App\Models\Reading;
use App\Enums\Permission;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use App\Livewire\Forms\ReadingForm;

new class extends Component {
    public ReadingForm $form;

    #[Url]
    public $tab = "details";

    public function mount(Reading $reading): void
    {
        $this->form->setReading($reading);
    }

    public function save()
    {
        $this->form->update();

        Flux::toast("Reading updated.");
        return $this->redirect("/readings", navigate: true);
    }

    public function delete()
    {
        $this->form->reading->delete();

        Flux::toast("Reading deleted.");
        return $this->redirect("/readings", navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit Reading: {{ $form->title }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        <flux:badge color="{{ $form->reading->type->color() }}">{{ $form->reading->type->label() }}</flux:badge>
    </flux:subheading>

    <flux:tab.group variant="flush" class="mt-8">
        <flux:tabs wire:model="tab" scrollable>
            <flux:tab name="details" icon="book-open-text">Details</flux:tab>
            <flux:tab name="text" icon="text">Text</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <form wire:submit="save" class="flex flex-col lg:flex-row gap-4 lg:gap-6">
                <div class="w-80">
                    <flux:heading size="lg">Reading Details</flux:heading>
                    <flux:subheading>Information about the reading.</flux:subheading>
                </div>

                <div class="flex-1 max-w-md space-y-6">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input type="text" name="title" wire:model="form.title" />
                        <flux:error name="form.title" />
                    </flux:field>

                    <flux:select variant="listbox" badge="Required" label="Type" wire:model="form.type" placeholder="Select the type..." required>
                        @foreach(\App\Enums\ReadingType::cases() as $readingType)
                            <flux:select.option value="{{ $readingType->value }}">{{ $readingType->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex space-x-2">
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </div>
            </form>
        </flux:tab.panel>
        <flux:tab.panel name="text">
            <form wire:submit="save" class="space-y-6">
                <flux:editor label="Text" wire:model="form.text" toolbar="heading | bold italic underline ~ undo redo" class="**:data-[slot=content]:min-h-[400px]" />
                <div class="flex">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </form>
        </flux:tab.panel>
    </flux:tab.group>
</section>
