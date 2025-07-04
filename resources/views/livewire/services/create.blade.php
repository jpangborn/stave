<?php

use App\Models\Template;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use App\Livewire\Actions\CreateServiceFromTemplate;
use App\Livewire\Forms\ServiceForm;

new class extends Component {
    public ServiceForm $form;

    public function save(CreateServiceFromTemplate $createServiceFromTemplate)
    {
        if ($this->form->template_id) {
            $template = Template::find($this->form->template_id)
                ->with("liturgyElements")
                ->first();
            $createServiceFromTemplate($template, $this->form->date);
        } else {
            $this->form->store();
        }

        Flux::toast(variant: "success", text: "Service added.");
        return $this->redirect("/services", navigate: true);
    }

    #[Computed]
    public function templates()
    {
        return Template::all();
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Add a Service</flux:heading>
    <flux:subheading size="lg" class="mb-6">Fill in details about the service.</flux:subheading>

    <form wire:submit="save">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mt-8">
            <div class="w-80">
                <flux:heading size="lg">Service Details</flux:heading>
                <flux:subheading>Information about the service.</flux:subheading>
            </div>

            <div class="flex-1 max-w-md space-y-6">
                <flux:field>
                    <flux:label badge="Required">Date</flux:label>
                    <flux:date-picker wire:model="form.date" name="date" />
                    <flux:error name="form.date" />
                </flux:field>

                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input type="text" name="name" wire:model="form.name" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Required">Base Template</flux:label>
                    <flux:select variant="listbox" searchable placeholder="Choose template...">
                        @foreach($this->templates as $template)
                            <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </div>
    </form>
</section>
