<?php

use App\Models\LiturgyElement;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

new class extends Component {
    #[Reactive]
    public int $serviceId;

    public Service $service;

    public function mount()
    {
        $this->loadService();
    }

    public function loadService()
    {
        $this->service = Service::with("liturgyElements")->find(
            $this->serviceId,
        );
    }

    #[On("service-element-changed")]
    public function refreshElements(): void
    {
        $this->loadService();
    }

    public function sort($item, $position): void
    {
        $liturgyElement = $this->service->liturgyElements()->findOrFail($item);

        DB::transaction(function () use ($liturgyElement, $position) {
            $before = $liturgyElement->order;
            $after = $position;

            if ($before === $after) {
                return;
            }

            $liturgyElement->update(["order" => 65535]);

            $elementsToShift = $this->service
                ->liturgyElements()
                ->whereBetween("order", [
                    min($before, $after),
                    max($before, $after),
                ]);

            $shiftUp = $before < $after;

            $shiftUp
                ? $elementsToShift->decrement("order")
                : $elementsToShift->increment("order");

            $liturgyElement->update(["order" => $after]);
        });

        $this->loadService();
        Flux::toast(variant: "success", text: "Service reordered.");
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
    <flux:table.rows x-sort="$wire.sort($item, $position)" x-sort:config="{ handle: '[x-sort-handle]' }">
        @if($this->service->liturgyElements->isEmpty())
            <flux:table.row>
                <flux:table.cell align="center">No Service Elements</flux:table.cell>
            </flux:table.row>
        @else
            @foreach($this->service->liturgyElements as $element)
                @livewire($element->type->component(), ['element' => $element], key($element->id))
            @endforeach
        @endif
    </flux:table.rows>
</flux:table>
