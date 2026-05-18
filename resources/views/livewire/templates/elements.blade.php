<?php


use App\Enums\LiturgyElementType;
use App\Livewire\Forms\LiturgyElementForm;
use App\Models\LiturgyElement;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

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
        return User::orderBy("name")->get();
    }

    /**
     * Walk the ordered elements once and tag each with its enclosing
     * section's color + first/last-in-section flags.
     *
     * @return list<array<string, mixed>>
     */
    public function layout(): array
    {
        $elements = $this->template->liturgyElements;
        $out = [];
        $currentSection = null;
        $sectionIndex = 0;
        $sectionElementCount = 0;
        $sectionBufferStart = 0;

        foreach ($elements as $el) {
            if ($el->type === LiturgyElementType::SECTION) {
                if ($currentSection !== null) {
                    for ($j = $sectionBufferStart; $j < count($out); $j++) {
                        $out[$j]['section_element_count'] = $sectionElementCount;
                    }
                    $out[$sectionBufferStart - 1]['section_element_count'] = $sectionElementCount;
                }
                $sectionIndex++;
                $currentSection = $el;
                $sectionElementCount = 0;
                $sectionBufferStart = count($out) + 1;

                $out[] = [
                    'element' => $el,
                    'section_color' => $el->section_color,
                    'section_index' => $sectionIndex,
                    'is_first_in_section' => false,
                    'is_last_in_section' => false,
                    'section_element_count' => 0,
                ];
                continue;
            }

            $sectionElementCount++;
            $isFirst = ! isset($out[count($out) - 1]) || $out[count($out) - 1]['element']->type === LiturgyElementType::SECTION;
            $out[] = [
                'element' => $el,
                'section_color' => $currentSection?->section_color,
                'section_index' => $currentSection !== null ? $sectionIndex : null,
                'is_first_in_section' => $isFirst,
                'is_last_in_section' => false,
                'section_element_count' => null,
            ];
        }

        $lastByIndex = [];
        foreach ($out as $idx => $row) {
            if ($row['element']->type !== LiturgyElementType::SECTION) {
                $lastByIndex[$row['section_index']] = $idx;
            }
        }
        foreach ($lastByIndex as $lastIdx) {
            $out[$lastIdx]['is_last_in_section'] = true;
        }

        if ($currentSection !== null) {
            for ($j = $sectionBufferStart; $j < count($out); $j++) {
                if ($out[$j]['element']->type !== LiturgyElementType::SECTION) {
                    $out[$j]['section_element_count'] = $sectionElementCount;
                }
            }
            $headerIdx = $sectionBufferStart - 1;
            if ($headerIdx >= 0 && isset($out[$headerIdx])) {
                $out[$headerIdx]['section_element_count'] = $sectionElementCount;
            }
        }

        return $out;
    }

    #[On("service-element-changed")]
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
    <div x-sort="$wire.sort($item, $position)" x-sort:config="{ handle: '[x-sort-handle]' }" class="mt-2">
        @php($rows = $this->layout())

        @if (empty($rows))
            <div class="rounded-lg border border-dashed border-zinc-300 px-6 py-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                No template elements yet.
            </div>
        @else
            @foreach ($rows as $row)
                @livewire(
                    $row['element']->type->component(),
                    [
                        'element' => $row['element'],
                        'users' => $this->users,
                        'sectionColor' => $row['section_color'],
                        'sectionIndex' => $row['section_index'],
                        'sectionElementCount' => $row['section_element_count'],
                        'isFirstInSection' => $row['is_first_in_section'],
                        'isLastInSection' => $row['is_last_in_section'],
                    ],
                    key('liturgy-element-'.$row['element']->id)
                )
            @endforeach
        @endif
    </div>

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
