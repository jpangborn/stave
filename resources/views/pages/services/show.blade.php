<?php

use App\Livewire\Forms\ServiceForm;
use App\Models\Service;
use App\Models\Template;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    public ServiceForm $form;

    #[Url]
    public $tab = 'service-order';

    public bool $saved = true;

    protected $listeners = [
        'refreshParent' => '$refresh',
        'service-element-changed' => '$refresh',
    ];

    public function mount(Service $service): void
    {
        $this->form->setService($service);
    }

    public function updatedFormTitle(): void
    {
        $this->form->saveTitle();
        $this->saved = true;
        Flux::toast(variant: 'success', text: 'Service renamed.');
    }

    public function updatingFormTitle(): void
    {
        $this->saved = false;
    }

    public function viewBulletin(): void
    {
        $this->tab = 'bulletin';
    }

    public function duplicate(): void
    {
        $clone = $this->form->duplicate();
        Flux::toast(variant: 'success', text: 'Service duplicated.');
        $this->redirectRoute('services.show', ['service' => $clone], navigate: true);
    }

    #[Computed]
    public function templates()
    {
        return Template::all();
    }
};
?>

<section class="w-full">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <x-service.date-block :date="$form->date" />

        <div class="min-w-0 flex-1">
            <div class="text-[11.5px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                {{ $form->date->format('l') }} · {{ $form->date->format('F j, Y') }}
            </div>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                <x-service.inline-text
                    wire-model="form.title"
                    :value="$form->title"
                    placeholder="Untitled Service"
                    class="text-3xl font-bold tracking-tight"
                />
            </h1>

            <div class="mt-2 flex flex-wrap items-center gap-2.5 text-[12.5px] text-zinc-500 dark:text-zinc-400">
                @if ($form->service?->template)
                    <span>Template:</span>
                    <a href="{{ route('templates.show', ['template' => $form->service->template]) }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-0.5 text-[11.5px] font-medium text-zinc-900 no-underline hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                        <flux:icon name="document-text" class="size-3 text-zinc-500" />
                        {{ $form->service->template->name }}
                    </a>
                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                @endif

                <span class="inline-flex items-center gap-1 text-[11.5px]">
                    @if ($saved)
                        <flux:icon name="check" class="size-3 text-emerald-600 dark:text-emerald-400" />
                        All changes saved
                    @else
                        <span class="size-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                        Saving…
                    @endif
                </span>
            </div>
        </div>

        <div class="flex shrink-0 gap-2">
            <flux:button size="sm" variant="ghost" wire:click="duplicate" icon="document-duplicate">
                Duplicate
            </flux:button>
            <flux:button size="sm" variant="ghost" wire:click="viewBulletin" icon="document-text">
                View Bulletin
            </flux:button>
        </div>
    </header>

    <x-service.stats-strip :service="$form->service" />

    <flux:tab.group class="mt-6">
        <flux:tabs wire:model.live="tab" scrollable>
            <flux:tab name="service-order" icon="queue-list">Service Order</flux:tab>
            <flux:tab name="discussion" icon="chat-bubble-left-right">Discussion</flux:tab>
            <flux:tab name="bulletin" icon="document-text">Bulletin</flux:tab>
            <flux:tab name="podium-notes" icon="lectern">Podium Notes</flux:tab>
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

        <flux:tab.panel name="podium-notes">
            <livewire:services.podium-notes :service-id="$form->service->id" />
        </flux:tab.panel>
    </flux:tab.group>
</section>
