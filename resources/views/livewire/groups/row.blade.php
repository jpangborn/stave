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

<flux:table.row class="group">
    <flux:table.cell>
        <div class="flex items-center gap-3">
            <div class="shrink-0 w-11">
                <x-groups.cover
                    :group="$group"
                    height="44px"
                    rounded="md"
                    initialSize="20px"
                />
            </div>
            <div class="min-w-0">
                <a
                    href="{{ route('groups.show', $group) }}"
                    wire:navigate
                    class="font-semibold text-accent-content hover:underline"
                >{{ $group->name }}</a>
                @if ($group->description)
                    <div class="text-xs text-zinc-500 line-clamp-1 max-w-md">
                        {{ trim(html_entity_decode(strip_tags((string) $group->description), ENT_QUOTES | ENT_HTML5, 'UTF-8')) }}
                    </div>
                @endif
            </div>
        </div>
    </flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" inset="top bottom" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" inset="top bottom" :color="$group->messaging->color()">{{ $group->messaging->label() }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $group->members_count ?? $group->members()->count() }}</flux:table.cell>
    <flux:table.cell class="whitespace-nowrap">{{ $group->created_at->toFormattedDayDateString() }}</flux:table.cell>
    <flux:table.cell align="end">
        <div class="flex items-center justify-end gap-1 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
            @can('join', $group)
                <flux:button
                    size="sm"
                    variant="ghost"
                    wire:click="$parent.join({{ $group->id }})"
                    icon="user-plus"
                >Join</flux:button>
            @endcan

            @can('update', $group)
                <flux:dropdown align="end" offset="-15">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom"/>

                    <flux:menu class="min-w-32">
                        <flux:menu.item :href="route('groups.edit', $group)" icon="pencil-square">Edit</flux:menu.item>
                        @can('delete', $group)
                            <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
                        @endcan
                    </flux:menu>
                </flux:dropdown>

                @can('delete', $group)
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
                @endcan
            @endcan
        </div>
    </flux:table.cell>
</flux:table.row>
