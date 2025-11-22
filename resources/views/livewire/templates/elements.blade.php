<?php

use App\Livewire\Forms\LiturgyElementForm;
use App\Models\LiturgyElement;
use App\Models\Template;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public int $templateId;

    public Template $template;
    public LiturgyElementForm $elementForm;

    public function mount()
    {
        $this->loadTemplate();
    }

    public function loadTemplate()
    {
        $this->template = Template::with("liturgyElements")->find(
            $this->templateId,
        );
    }

    #[Computed]
    public function users()
    {
        return User::all();
    }

    #[On("related-model-changed")]
    public function refreshElements(): void
    {
        $this->loadTemplate();
    }

    public function sort($item, $position): void
    {
        $liturgyElement = $this->template->liturgyElements()->findOrFail($item);

        DB::transaction(function () use ($liturgyElement, $position) {
            $before = $liturgyElement->order;
            $after = $position;

            if ($before === $after) {
                return;
            }

            $liturgyElement->update(["order" => 65535]);

            $elementsToShift = $this->template
                ->liturgyElements()
                ->whereBetween("order", [
                    min($before, $after),
                    max($before, $after),
                ]);

            $shiftUp = $before < $after;

            $shiftUp
                ? $elementsToShift->decrement("order")
                : $elementsToShift->increment("order");

            $liturgyElement->update(["order" => $after]);
        });

        $this->loadTemplate();
        Flux::toast(variant: "success", text: "Template reordered.");
    }

    public function delete($id): void
    {
        $this->template->liturgyElements()->findOrFail($id)->delete();
        Flux::modal("delete-element")->close();
        Flux::toast(variant: "danger", text: "Liturgy element deleted.");
    }

    #[On('edit-element')]
    public function editElement($id): void
    {
        $element = $this->template->liturgyElements()->findOrFail($id);
        $this->elementForm->setLiturgyElement($element);
        Flux::modal("edit-element")->show();
    }

    public function updateElement(): void
    {
        $this->elementForm->update();
        $this->reset("elementForm");
        $this->loadTemplate();
        Flux::modal("edit-element")->close();
        Flux::toast(variant: "success", text: "Element updated.");
    }
};
?>

<div>
    <flux:table class="w-full">
        <flux:table.rows x-sort="$wire.sort($item, $position)" x-sort:config="{ handle: '[x-sort\\:handle]' }">
            @if($this->template->liturgyElements->isEmpty())
                <flux:table.row>
                    <flux:table.cell align="center">No Service Elements</flux:table.cell>
                </flux:table.row>
            @else
                @foreach($this->template->liturgyElements as $element)
                    @livewire($element->type->component(), ['element' => $element], key($element->id))
                @endforeach
            @endif
        </flux:table.rows>
    </flux:table>

    <flux:modal variant="flyout" name="edit-element">
    <form wire:submit="updateElement" class="space-y-6">
        <div>
            <flux:heading size="lg">Edit Liturgy Element</flux:heading>
        </div>

        <flux:select label="Type" variant="listbox" wire:model="elementForm.type">
            @foreach(App\Enums\LiturgyElementType::cases() as $element)
                <flux:select.option value="{{ $element->value }}">
                    <div class="flex items-center gap-x-2">
                        <flux:icon name="{{ $element->icon() }}" />{{ $element->label() }}
                    </div>
                </flux:select.option>
            @endforeach
        </flux:select>

        @if(isset($elementForm->type) && $elementForm->type === App\Enums\LiturgyElementType::READING->value)
        <flux:select label="Reading Type" variant="listbox" wire:model="elementForm.reading_type">
            @foreach(App\Enums\ReadingType::cases() as $reading_type)
                <flux:select.option value="{{ $reading_type->value }}">{{ $reading_type->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        @endif

        <flux:field>
            <flux:label for="element_name">Name</flux:label>
            <flux:input id="element_name" placeholder="Enter a name..." wire:model="elementForm.name" />
            <flux:error name="elementForm.name" />
        </flux:field>

        <flux:field>
            <flux:label for="element_description">Description</flux:label>
            <flux:input id="element_description" wire:model="elementForm.description" />
            <flux:error name="elementForm.description" />
        </flux:field>

        <flux:select label="Assignee" variant="listbox" wire:model="elementForm.assignee_id" placeholder="Select an assignee...">
            @foreach($this->users as $user)
                <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex">
            <flux:spacer />
            <flux:button type="submit" variant="primary">Update Element</flux:button>
        </div>
    </form>
    </flux:modal>
</div>
