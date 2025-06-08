<?php

use Livewire\Volt\Component;
use App\Models\ServiceElement;

new class extends Component {
    public ServiceElement $element;
}; ?>

<flux:table.row>
    <flux:table.cell class="bg-zinc-50 dark:bg-zinc-900">
        <div class="pl-2">
            <flux:heading size="lg">{{ $element->name }}</flux:heading>
            <flux:subheading>{{ $element->description }}</flux:subheading>
        </div>
    </flux:table.cell>
</flux:table.row>
