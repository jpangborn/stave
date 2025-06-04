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

    protected $listeners = [
        "refreshParent" => '$refresh',
    ];

    public function mount(Reading $reading)
    {
        $this->form->setReading($reading);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ $form->title }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        <flux:badge color="{{ $form->reading->type->color() }}">{{ $form->reading->type->label() }}</flux:badge>
    </flux:subheading>

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model="tab">
            <flux:tab name="details" icon="book-open-text">Text</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <div class="max-w-lg [&>p]:mb-8">
                {!! $form->text !!}
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</section>
