<?php

use App\Models\LiturgyElement;
use App\Models\Service;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public int $serviceId;

    public function getServiceProperty()
    {
        return Service::with("liturgyElements")->find($this->serviceId);
    }

    #[On("related-model-added")]
    public function refreshElements(): void
    {
        // Force re-computation of the template property
        unset($this->service);
    }

    public function delete($id): void
    {
        LiturgyElement::findOrFail($id)->delete();
        Flux::modal("delete-element")->close();
        Flux::toast(variant: "danger", text: "Liturgy element deleted.");
    }
};
?>

<flux:table class="w-full">
    <flux:table.rows x-sort>
        @if($this->service->liturgyElements->isEmpty())
            <flux:table.row>
                <flux:table.cell align="center">No Service Elements</flux:table.cell>
            </flux:table.row>
        @else
            @foreach($this->service->liturgyElements as $element)
                @switch($element->type)
                    @case(App\Enums\LiturgyElementType::SECTION)
                        <livewire:elements.section :$element :key="$element->id" x-sort:item />
                        @break
                    @case(App\Enums\LiturgyElementType::SONG)
                        <livewire:elements.song :$element :key="$element->id" x-sort:item />
                        @break
                    @case(App\Enums\LiturgyElementType::READING)
                    @case(App\Enums\LiturgyElementType::PRAYER)
                        <livewire:elements.reading :$element :key="$element->id" x-sort:item />
                        @break
                    @case(App\Enums\LiturgyElementType::SERMON)
                        <livewire:elements.sermon :$element :key="$element->id" x-sort:item />
                        @break
                    @default
                        <livewire:elements.other :$element :key="$element->id" x-sort:item />
                        @break
                @endswitch
            @endforeach
        @endif
    </flux:table.rows>
</flux:table>
