<?php

use App\Models\LiturgyElement;
use App\Models\Template;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public int $templateId;

    public function getTemplateProperty()
    {
        return Template::with("liturgyElements")->find($this->templateId);
    }

    #[On("related-model-added")]
    public function refreshElements()
    {
        // Force re-computation of the template property
        unset($this->template);
    }

    public function delete($id)
    {
        LiturgyElement::findOrFail($id)->delete();
        Flux::modal("delete-element")->close();
        Flux::toast(variant: "danger", text: "Liturgy element deleted.");
    }
};
?>

<flux:table class="w-full">
    <flux:table.rows>
        @if($this->template->liturgyElements->isEmpty())
            <flux:table.row>
                <flux:table.cell align="center">No Service Elements</flux:table.cell>
            </flux:table.row>
        @else
            @foreach($this->template->liturgyElements as $element)
                @switch($element->type)
                    @case(App\Enums\LiturgyElementType::SECTION)
                        <livewire:elements.section :$element :key="$element->id" />
                        @break
                    @case(App\Enums\LiturgyElementType::SONG)
                        <livewire:elements.song :$element :key="$element->id" />
                        @break
                    @case(App\Enums\LiturgyElementType::READING)
                    @case(App\Enums\LiturgyElementType::PRAYER)
                        <livewire:elements.reading :$element :key="$element->id" />
                        @break
                    @case(App\Enums\LiturgyElementType::SERMON)
                        <livewire:elements.sermon :$element :key="$element->id" />
                        @break
                    @default
                        <livewire:elements.other :$element :key="$element->id" />
                        @break
                @endswitch
            @endforeach
        @endif
    </flux:table.rows>
</flux:table>
