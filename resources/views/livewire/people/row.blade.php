<?php

use App\Models\Person;
use Livewire\Volt\Component;

new class extends Component {
    public Person $person;

    public function delete()
    {
        $this->modal("delete-person")->show();
    }
};
?>

<flux:table.row>
    <flux:table.cell>
        <flux:link variant="subtle" href="{{ route('people.show', ['person' => $person]) }}">
            <div class="flex w-max items-center gap-2">
                <flux:avatar name="{{ $person->name }}" src="{{ $person->gravatar }}" />
                <div class="flex flex-col">
                    <span>{{ $person->fullName }}</span>
                    <span>{{ $person->email }}</span>
                </div>
            </div>
        </flux:link>
    </flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" inset="top bottom" :color="$person->gender?->color()">{{ $person->gender?->label() }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell class="whitespace-nowrap">{{ $person->created_at->toFormattedDayDateString() }}</flux:table.cell>
    <flux:table.cell class="max-w-6">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

            <flux:menu class="min-w-32">
                <flux:menu.item href="{{ route('people.edit', ['person' => $person]) }}" icon="pencil-square">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:drowdown>

        <flux:modal name="delete-person" class="min-w-[22rem]">
            <form wire:submit="$parent.delete({{ $person->id }})" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete person?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the person.</p>
                        <p>It cannot be undone.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete person</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
