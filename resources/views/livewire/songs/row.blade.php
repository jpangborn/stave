<?php

use App\Models\Song;
use Livewire\Volt\Component;

new class extends Component {
    public Song $song;

    public function delete(): void
    {
        $this->modal("delete-song")->show();
    }
};
?>

<flux:table.row>
    <flux:table.cell>
        <flux:link variant="ghost" href="{{ route('songs.show', ['song' => $song]) }}" >{{ $song->name }}</flux:link>
    </flux:table.cell>
    <flux:table.cell>
        @if($song->ccli_number)
            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $song->ccli_number }}</flux:badge>
        @endif
    </flux:table.cell>
    <flux:table.cell>
        @if($song->last_used_date)
            {{ $song->last_used_date->toFormattedDayDateString() }}
        @else
            <span class="text-zinc-500">Never</span>
        @endif
    </flux:table.cell>
    <flux:table.cell>
        {{ $song->created_at->toFormattedDayDateString() }}
    </flux:table.cell>
    <flux:table.cell class="max-w-6">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

            <flux:menu class="min-w-32">
                <flux:menu.item href="{{ route('songs.edit', ['song' => $song]) }}" icon="pencil-square"  class="cursor-default">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <flux:modal name="delete-song" class="min-w-[22rem]">
            <form wire:submit="$parent.delete({{ $song->id }})" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete song?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the song and associated recordings and sheets.</p>
                        <p>It cannot be undone.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete song</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
