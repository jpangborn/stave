<?php

use App\Models\Service;
use App\Enums\Permission;
use App\Models\Template;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use App\Livewire\Forms\ServiceForm;

new class extends Component {
    public ServiceForm $form;

    #[Url]
    public $tab = "details";

    protected $listeners = [
        "refreshParent" => '$refresh',
    ];

    public function mount(Service $service)
    {
        $this->form->setService($service);
    }

    #[Computed]
    public function templates()
    {
        return Template::all();
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ $form->title }}</flux:heading>
    <flux:subheading>{{ $form->date->toFormattedDayDateString() }} @if($form->template_id) - {{ $form->service->template->name }}@endif</flux:subheading>

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model="tab">
            <flux:tab name="details" icon="notepad-text-dashed">Details</flux:tab>
            <flux:tab name="elements" icon="list-collapse">Elements</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <form wire:submit="save" class="flex flex-col lg:flex-row gap-4 lg:gap-6">
                <div class="w-80">
                    <flux:heading size="lg">Service Details</flux:heading>
                    <flux:subheading>Information about the service.</flux:subheading>
                </div>

                <div class="flex-1 max-w-md space-y-6">
                    <flux:field>
                        <flux:label badge="Required">Date</flux:label>
                        <flux:date-picker name="date" wire:model="form.date" with-today disabled />
                        <flux:error name="form.date" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input type="text" name="name" wire:model="form.title" disabled />
                        <flux:error name="form.title" />
                    </flux:field>

                    <flux:field>
                        <flux:label badge="Required">Base Template</flux:label>
                        <flux:select variant="listbox" wire:model="form.template_id" placeholder="Choose template..." disabled>
                            @foreach($this->templates as $template)
                                <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </form>
        </flux:tab.panel>
    </flux:tab.group>
</section>
