<?php

use App\Models\Template;
use Livewire\Volt\Component;

new class extends Component {
    public Template $template;
};
?>

<flux:table class="w-full">
    <flux:table.rows>
        @if(!$template->serviceElements)
            <flux:table.row>
                <flux:table.cell align="center">No Service Elements</flux:table.cell>
            </flux:table.row>
        @else
            @foreach($template->serviceElements as $element)
                @switch($element->type)
                    @case(App\Enums\LiturgyElementType::SECTION)
                        <livewire:templates.elements.section :$element :key="$element->id" />
                        @break
                    @case(App\Enums\LiturgyElementType::SONG)
                        <livewire:templates.elements.song :$element :key="$element->id" />
                        @break
                    @case(App\Enums\LiturgyElementType::READING)
                        <livewire:templates.elements.reading :$element :key="$element->id" />
                        @break
                    @case(App\Enums\LiturgyElementType::SERMON)
                        <livewire:templates.elements.sermon :$element :key="$element->id" />
                        @break
                    @default
                        <livewire:templates.elements.reading :$element :key="$element->id" />
                        @break
                @endswitch
            @endforeach
        @endif
    </flux:table.rows>
</flux:table>
