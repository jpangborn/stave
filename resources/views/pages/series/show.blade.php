<?php

use App\Models\Series;
use App\Models\Reading;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Livewire\Forms\SeriesForm;
use Flux\Flux;

new class extends Component {
    public SeriesForm $form;

    public ?int $selectedReadingId = null;
    public ?int $selectedOrder = null;

    protected $listeners = [
        "refreshParent" => '$refresh',
    ];

    public function mount(Series $series): void
    {
        $this->form->setSeries($series);
        $this->selectedOrder = ($series->readings()->max('series_order') ?? 0) + 1;
    }

    #[Computed]
    public function availableReadings()
    {
        return Reading::query()
            ->whereNull('series_id')
            ->orderBy('title')
            ->get();
    }

    public function addReading(): void
    {
        if (!$this->selectedReadingId) {
            return;
        }

        $reading = Reading::find($this->selectedReadingId);
        if ($reading) {
            $reading->update([
                'series_id' => $this->form->series->id,
                'series_order' => $this->selectedOrder,
            ]);

            $this->selectedReadingId = null;
            $this->selectedOrder = $this->form->series->readings()->max('series_order') + 1;

            Flux::modal("add-reading")->close();
            Flux::toast(variant: "success", text: "Reading added to series.");
        }
    }

    public function removeReading(int $readingId): void
    {
        $reading = Reading::find($readingId);
        if ($reading && $reading->series_id === $this->form->series->id) {
            $reading->update([
                'series_id' => null,
                'series_order' => null,
            ]);

            Flux::toast(variant: "danger", text: "Reading removed from series.");
        }
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ $form->name }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        {{ $form->series->readings->count() }} {{ Str::plural('reading', $form->series->readings->count()) }}
    </flux:subheading>

    <div class="flex flex-col lg:flex-row gap-6 mt-8">
        <div class="w-80">
            <flux:heading size="lg">Description</flux:heading>
            @if($form->description)
                <div class="prose prose-sm dark:prose-invert mt-2">
                    {!! $form->description !!}
                </div>
            @else
                <flux:subheading>No description provided.</flux:subheading>
            @endif
        </div>

        <div class="flex-1">
            <div class="flex items-center gap-4 mb-4">
                <flux:heading size="lg">Readings</flux:heading>
                <flux:spacer />
                <flux:modal.trigger name="add-reading">
                    <flux:button size="sm" variant="primary" icon="plus">Add Reading</flux:button>
                </flux:modal.trigger>
            </div>

            @if($form->series->readings->isEmpty())
                <flux:card>
                    <flux:heading size="lg" level="3">No Readings Yet</flux:heading>
                    <flux:subheading>Add readings to this series to create a sequential collection.</flux:subheading>
                </flux:card>
            @else
                <div class="space-y-2">
                    @foreach($form->series->readings as $reading)
                        <flux:card class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold">
                                {{ $reading->series_order }}
                            </div>
                            <div class="flex-1">
                                <flux:link :href="route('readings.show', ['reading' => $reading])" class="font-medium">
                                    {{ $reading->title }}
                                </flux:link>
                                <div class="text-sm text-zinc-500">
                                    <flux:badge size="sm" color="{{ $reading->type->color() }}">{{ $reading->type->label() }}</flux:badge>
                                </div>
                            </div>
                            <flux:button wire:click="removeReading({{ $reading->id }})" size="sm" variant="ghost" icon="x-mark" />
                        </flux:card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <flux:modal name="add-reading" class="min-w-[24rem]">
        <form wire:submit="addReading" class="space-y-6">
            <div>
                <flux:heading size="lg">Add Reading to Series</flux:heading>
                <flux:subheading>Select a reading and specify its order in the series.</flux:subheading>
            </div>

            <flux:select variant="listbox" label="Reading" wire:model="selectedReadingId" placeholder="Select a reading..." searchable>
                @foreach($this->availableReadings as $reading)
                    <flux:select.option value="{{ $reading->id }}">{{ $reading->title }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:field>
                <flux:label>Order</flux:label>
                <flux:input type="number" wire:model="selectedOrder" min="1" />
                <flux:description>Position of this reading in the series</flux:description>
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Reading</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
