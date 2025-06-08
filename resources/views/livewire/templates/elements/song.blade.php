<?php

use App\Models\ServiceElement;
use Livewire\Volt\Component;

new class extends Component {
    public ServiceElement $element;
}; ?>

<flux:table.row>
    <flux:table.cell>
        <div class="flex items-center gap-x-2">
            <div>
                <flux:icon.musical-note />
            </div>
            <div>
                <flux:heading>{{ $element->name }}</flux:heading>
                @if($element->description)
                    <flux:subheading>{{ $element->description }}</flux:subheading>
                @endif
            </div>
        </div>
    </flux:table.cell>
</flux:table.row>
