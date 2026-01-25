<?php

use App\Models\Series;
use Livewire\Component;

new class extends Component {
    public Series $series;

    public function delete(): void
    {
        $this->modal("delete-series")->show();
    }
};
?>

<flux:table.row>
    <flux:table.cell>
        <flux:link variant="ghost" href="{{ route('series.show', ['series' => $series]) }}">{{ $series->name }}</flux:link>
    </flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $series->readings_count }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell>
        {{ $series->created_at->toFormattedDayDateString() }}
    </flux:table.cell>
    <flux:table.cell align="end">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

            <flux:menu class="min-w-32">
                <flux:menu.item href="{{ route('series.show', ['series' => $series]) }}" icon="eye" class="cursor-default">View</flux:menu.item>
                <flux:menu.item href="{{ route('series.edit', ['series' => $series]) }}" icon="pencil-square" class="cursor-default">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <flux:modal name="delete-series" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete series?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the series.</p>
                        <p>Readings will not be deleted.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="button" wire:click="$parent.delete({{ $series->id }})" variant="danger">Delete series</flux:button>
                </div>
            </div>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
