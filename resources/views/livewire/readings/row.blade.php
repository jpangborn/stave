<?php

use App\Models\Reading;
use App\Enums\Permission;
use Livewire\Volt\Component;

new class extends Component {
    public Reading $reading;

    public function delete()
    {
        $this->modal("delete-reading")->show();
    }
};
?>

<flux:table.row>
    <flux:table.cell>
        <flux:link variant="ghost" href="{{ route('readings.show', ['reading' => $reading]) }}" >{{ $reading->title }}</flux:link>
    </flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" color="{{ $reading->type->color() }}" inset="top bottom">{{ $reading->type->label() }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell>
        {{ $reading->created_at->toFormattedDayDateString() }}
    </flux:table.cell>
    <flux:table.cell align="end">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

            <flux:menu class="min-w-32">
                <flux:menu.item href="{{ route('readings.edit', ['reading' => $reading]) }}" icon="pencil-square" class="cursor-default">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:drowdown>
    </flux:table.cell>

    <flux:modal name="delete-song" class="min-w-[22rem]">
        <form wire:submit="$parent.delete({{ $reading->id }})" class="space-y-6">
            <div>
                <flux:heading size="lg">Delete reading?</flux:heading>

                <flux:subheading>
                    <p>This will permanently delete the reading.</p>
                    <p>It cannot be undone.</p>
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="danger">Delete reading</flux:button>
            </div>
        </form>
    </flux:modal>
</flux:table.row>
