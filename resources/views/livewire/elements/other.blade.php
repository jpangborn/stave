<?php
use App\Models\LiturgyElement;
use Livewire\Volt\Component;

new class extends Component {
    public LiturgyElement $element;

    public function delete()
    {
        $this->modal("delete-element")->show();
    }
};
?>

<flux:table.row :x-sort:item="$element->id">
    <flux:table.cell>
        <div class="flex flex-col md:flex-row gap-2 md:gap-x-2 md:items-center pl-1 group">
            <div x-sort-handle class="cursor-grab active:cursor-grabbing hidden group-hover:block" title="Drag to reorder">
                <flux:icon class="text-zinc-300" name="grip" />
            </div>
            <div>
                <flux:icon icon="{{ $element->type->icon() }}" />
            </div>
            <div class="flex-1">
                <flux:heading>{{ $element->name }}</flux:heading>
                @if($element->description)
                    <flux:subheading>{{ $element->description }}</flux:subheading>
                @endif
            </div>
            <div class="md:pr-2">
                <flux:dropdown align="end" offset="-15">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

                    <flux:menu class="min-w-32">
                        <flux:menu.item wire:click="$dispatch('edit-element', { id: {{ $element->id }} })" icon="pencil-square"  class="cursor-default">Edit</flux:menu.item>
                        <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        <flux:modal name="delete-element" class="min-w-[22rem]">
            <form wire:submit="$parent.delete({{ $element->id }})" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete liturgy element?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the liturgy element.</p>
                        <p>It cannot be undone.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete Element</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
