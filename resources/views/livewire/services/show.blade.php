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
    public $tab = "service-order";

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
    <div class="flex items-center">
        <header>
            <flux:heading size="xl" level="1">{{ $form->title }}</flux:heading>
            <flux:subheading>{{ $form->date->toFormattedDayDateString() }} @if($form->template_id) - Template: {{ $form->service->template->name }}@endif</flux:subheading>
        </header>
        <flux:spacer />
        <flux:button size="sm" variant="primary" href="{{ route('services.edit', ['service' => $form->service]) }}">Edit</flux:button>
    </div>

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model="tab">
            <flux:tab name="service-order" icon="queue-list">Service Order</flux:tab>
            <flux:tab name="discussion" icon="chat-bubble-left-right">Discussion</flux:tab>
            <flux:tab name="bulletin" icon="document-text">Bulletin</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="service-order">
            <livewire:services.elements :service-id="$form->service->id" />
        </flux:tab.panel>

        <flux:tab.panel name="discussion">
            <livewire:services.discussion :service-id="$form->service->id" />
        </flux:tab.panel>

        <flux:tab.panel name="bulletin">
            <livewire:services.bulletin :service-id="$form->service->id" />
        </flux:tab.panel>
    </flux:tab.group>
</section>
