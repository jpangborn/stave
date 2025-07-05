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
    <div class="flex items-center">
        <header>
            <flux:heading size="xl" level="1">{{ $form->title }}</flux:heading>
            <flux:subheading>{{ $form->date->toFormattedDayDateString() }} @if($form->template_id) - Template: {{ $form->service->template->name }}@endif</flux:subheading>
        </header>
        <flux:spacer />
        <flux:button size="sm" variant="primary" href="{{ route('services.edit', ['service' => $form->service]) }}">Edit</flux:button>
    </div>

    <div class="mt-6">
        <livewire:services.elements :service-id="$form->service->id" />
    </div>
</section>
