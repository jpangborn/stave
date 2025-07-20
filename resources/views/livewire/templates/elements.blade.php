<?php

use App\Models\LiturgyElement;
use App\Models\Template;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public int $templateId;

    public Template $template;

    public function mount()
    {
        $this->loadTemplate();
    }

    public function loadTemplate()
    {
        $this->template = Template::with("liturgyElements")->find(
            $this->templateId,
        );
    }

    #[On("related-model-changed")]
    public function refreshElements(): void
    {
        $this->loadTemplate();
    }

    public function sort($item, $position): void
    {
        $liturgyElement = $this->template->liturgyElements()->findOrFail($item);

        DB::transaction(function () use ($liturgyElement, $position) {
            $before = $liturgyElement->order;
            $after = $position;

            if ($before === $after) {
                return;
            }

            $liturgyElement->update(["order" => 65535]);

            $elementsToShift = $this->template
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

        $this->loadTemplate();
        Flux::toast(variant: "success", text: "Template reordered.");
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
    <flux:table.rows x-sort="$wire.sort($item, $position)">
        @if($this->template->liturgyElements->isEmpty())
            <flux:table.row>
                <flux:table.cell align="center">No Service Elements</flux:table.cell>
            </flux:table.row>
        @else
            @foreach($this->template->liturgyElements as $element)
                @livewire($element->type->component(), ['element' => $element], key($element->id))
            @endforeach
        @endif
    </flux:table.rows>
</flux:table>
