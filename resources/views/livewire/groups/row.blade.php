<?php

use App\Models\Group;
use Livewire\Component;

new class extends Component {
    public Group $group;

    public function delete(): void
    {
        $this->modal('delete-group')->show();
    }
};
?>

<flux:table.row>
    <flux:table.cell><a href="{{ route('groups.show', $group) }}" wire:navigate class="hover:underline">{{ $group->name }}</a></flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" inset="top bottom" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" inset="top bottom" :color="$group->messaging->color()">{{ $group->messaging->label() }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell class="whitespace-nowrap">{{ $group->created_at->toFormattedDayDateString() }}</flux:table.cell>
    <flux:table.cell align="end">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom"/>

            <flux:menu class="min-w-32">
                <flux:menu.item :href="route('groups.edit', $group)" icon="pencil-square">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <flux:modal name="delete-group" class="min-w-[22rem]">
            <form wire:submit="$parent.delete({{ $group->id }})" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete group?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the group.</p>
                        <p>It cannot be undone.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer/>

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete group</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
