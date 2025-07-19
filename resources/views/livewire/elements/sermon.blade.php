<?php

use App\Models\LiturgyElement;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public LiturgyElement $element;

    public $description;
    public $assigneeId;

    public function mount(): void
    {
        $this->description = $this->element->description;
        $this->assigneeId = $this->element->assignee_id;
    }

    public function updated($name, $value): void
    {
        switch ($name) {
            case "description":
                $this->element->description = $value;
                $this->element->save();
                Flux::toast(
                    variant: "success",
                    text: "Sermon description saved.",
                );
                break;
            case "assigneeId":
                $this->element->assignee_id = $value;
                $this->element->save();
                Flux::toast(variant: "success", text: "Assignee saved.");
                break;
        }
    }

    public function delete(): void
    {
        $this->modal("delete-element")->show();
    }

    #[Computed]
    public function users()
    {
        return User::all();
    }
};
?>

<flux:table.row>
    <flux:table.cell>
        <div class="flex items-center gap-x-2 pl-1 group">
            <div x-sort-handle class="cursor-grab hidden group-hover:block" title="Drag to reorder">
                <flux:icon class="text-zinc-300" name="grip" />
            </div>
            <div>
                <flux:icon.lectern />
            </div>
            <div>
                <flux:heading>{{ $element->name }}</flux:heading>
            </div>
            <flux:spacer />
            <div>
                <flux:select variant="combobox" size="sm" wire:model.live="assigneeId" placeholder="Assign element...">
                    @foreach($this->users as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-[226px]">
                <flux:input size="sm" placeholder="Enter title or reference" wire:model.blur="description" />
            </div>
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

        <flux:modal name="delete-element" class="min-w-[22rem]">
            <form wire:submit="$parent.delete({{ $element->id }})" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete song?</flux:heading>

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
