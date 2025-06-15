<?php

use Livewire\Volt\Component;
use App\Models\LiturgyElement;

new class extends Component {
    public LiturgyElement $element;
};
?>

<flux:table.row>
    <flux:table.cell class="bg-zinc-50 dark:bg-zinc-900">
        <div class="flex items-center gap-x-2">
            <div class="pl-2">
                <flux:heading size="lg">{{ $element->name }}</flux:heading>
                <flux:subheading>{{ $element->description }}</flux:subheading>
            </div>
            <flux:spacer />
            <div class="pr-2">
                <flux:dropdown align="end" offset="-15">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

                    <flux:menu class="min-w-32">
                        <flux:menu.item wire:click="editElement({{ $element->id }})" icon="pencil-square"  class="cursor-default">Edit</flux:menu.item>
                        <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
                    </flux:menu>
                </flux:drowdown>
            </div>
        </div>
    </flux:table.cell>
</flux:table.row>
