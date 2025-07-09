<?php

use App\Models\Reading;
use App\Enums\Permission;
use Livewire\Volt\Component;

new class extends Component {
    public Reading $reading;

    public function delete(): void
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
    <flux:table.cell  class="max-w-6">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

            <flux:menu class="min-w-32">
                <flux:menu.item href="{{ route('readings.edit', ['reading' => $reading]) }}" icon="pencil-square" class="cursor-default">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:drowdown>

        <flux:modal name="delete-reading" class="min-w-[22rem]">
            <div class="space-y-6">
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

                    <flux:button type="button" wire:click="$parent.delete({{ $reading->id }})" variant="danger">Delete reading</flux:button>
                </div>
            </div>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
