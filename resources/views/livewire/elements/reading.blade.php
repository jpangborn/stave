<?php

use App\Models\LiturgyElement;
use App\Models\Reading;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public LiturgyElement $element;

    public $selectedContent;
    public $assigneeId;

    public function mount(): void
    {
        $this->selectedContent = $this->element->content_id;
        $this->assigneeId = $this->element->assignee_id;
    }

    public function updated($name, $value): void
    {
        switch ($name) {
            case "selectedContent":
                $reading = Reading::findOrFail($value);
                $this->element->content()->associate($reading);
                $this->element->save();
                $this->element->refresh();
                $this->selectedContent = $this->element->content_id;
                $this->dispatch('service-element-changed');
                Flux::toast(
                    variant: "success",
                    text: "Reading selection saved.",
                );
                break;
            case "assigneeId":
                $this->element->assignee_id = $value;
                $this->element->save();
                $this->element->refresh();
                $this->assigneeId = $this->element->assignee_id;
                $this->dispatch('service-element-changed');
                Flux::toast(variant: "success", text: "Assignee saved.");
                break;
        }
    }

    public function delete(): void
    {
        $this->modal("delete-element")->show();
    }

    #[Computed]
    public function readings()
    {
        return $this->element->reading_type
            ? Reading::where('type', $this->element->reading_type)->orderBy('title')->get()
            : Reading::orderBy('title')->get();
    }

    #[Computed]
    public function users()
    {
        return User::all();
    }
};
?>

<flux:table.row :x-sort:item="$element->id">
    <flux:table.cell>
        <div class="flex flex-col md:flex-row gap-2 md:gap-x-2 md:items-center pl-1 group">
            <div x-sort-handle class="cursor-grab hidden group-hover:block" title="Drag to reorder">
                <flux:icon class="text-zinc-300" name="grip" />
            </div>
            <div class="flex-1 flex items-center gap-1">
                <div>
                    <flux:icon icon="{{ $element->type->icon() }}" />
                </div>
                <div>
                    <flux:heading>{{ $element->name }}</flux:heading>
                    @if($element->description)
                        <flux:subheading>{{ $element->description }}</flux:subheading>
                    @endif
                </div>
            </div>
            <div>
                <flux:select variant="combobox" size="sm" wire:model.live="assigneeId" placeholder="Assign element...">
                    @foreach($this->users as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select variant="combobox" size="sm" wire:model.live="selectedContent" placeholder="Select a reading...">
                    @foreach($this->readings as $reading)
                        <flux:select.option value="{{ $reading->id }}">{{ $reading->title }}</flux:option>
                    @endforeach
                </flux:select>
            </div>
            <div class="hidden md:block md:pr-2">
                <flux:dropdown align="end" offset="-15">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

                    <flux:menu class="min-w-32">
                        <flux:menu.item href="{{ route('readings.create') }}" icon="plus-circle">New Reading</flux:menu.item>
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
