<?php

use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

new class extends Component {
    #[Reactive]
    public int $serviceId;

    public Service $service;

    public function mount(): void
    {
        $this->loadService();
    }

    public function loadService(): void
    {
        $this->service = Service::with('liturgyElements')->findOrFail($this->serviceId);
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
    }

    #[Computed]
    public function recentAssigneeIds(): array
    {
        return $this->service->liturgyElements
            ->pluck('assignee_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Walk the ordered elements once and tag each with its enclosing
     * section's color + first/last-in-section flags. Returns a list of
     * ['element' => LiturgyElement, 'section_color' => ?string, 'section_index' => int|null,
     *   'is_first_in_section' => bool, 'is_last_in_section' => bool, 'section_element_count' => int|null].
     *
     * @return list<array<string, mixed>>
     */
    public function layout(): array
    {
        $elements = $this->service->liturgyElements;
        $out = [];
        $currentSection = null;
        $sectionIndex = 0;
        $sectionElementCount = 0;
        $sectionBufferStart = 0;

        foreach ($elements as $i => $el) {
            if ($el->type === LiturgyElementType::SECTION) {
                // close out the prior section's element-count back-references
                if ($currentSection !== null) {
                    for ($j = $sectionBufferStart; $j < count($out); $j++) {
                        $out[$j]['section_element_count'] = $sectionElementCount;
                    }
                    $out[$sectionBufferStart - 1]['section_element_count'] = $sectionElementCount;
                }
                $sectionIndex++;
                $currentSection = $el;
                $sectionElementCount = 0;
                $sectionBufferStart = count($out) + 1; // index of the first child below this header

                $out[] = [
                    'element' => $el,
                    'section_color' => $el->section_color,
                    'section_index' => $sectionIndex,
                    'is_first_in_section' => false,
                    'is_last_in_section' => false,
                    'section_element_count' => 0, // patched below
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
                'is_last_in_section' => false, // patched after the loop
                'section_element_count' => null,
            ];
        }

        // Patch last-in-section flags and the final section's element-count back-references.
        $prevSectionStart = null;
        $lastByIndex = [];
        foreach ($out as $idx => $row) {
            if ($row['element']->type === LiturgyElementType::SECTION) {
                $prevSectionStart = $idx;
            } else {
                $lastByIndex[$row['section_index']] = $idx;
            }
        }
        foreach ($lastByIndex as $lastIdx) {
            $out[$lastIdx]['is_last_in_section'] = true;
        }

        // Final section count patch
        if ($currentSection !== null) {
            for ($j = $sectionBufferStart; $j < count($out); $j++) {
                if ($out[$j]['element']->type !== LiturgyElementType::SECTION) {
                    $out[$j]['section_element_count'] = $sectionElementCount;
                }
            }
            // Also patch the section header row itself with its count
            $headerIdx = $sectionBufferStart - 1;
            if ($headerIdx >= 0 && isset($out[$headerIdx])) {
                $out[$headerIdx]['section_element_count'] = $sectionElementCount;
            }
        }

        return $out;
    }

    #[On('service-element-changed')]
    public function refreshElements(): void
    {
        $this->loadService();
    }

    public function sort($item, $position): void
    {
        $liturgyElement = $this->service->liturgyElements()->findOrFail($item);

        DB::transaction(function () use ($liturgyElement, $position) {
            $before = $liturgyElement->order;
            $after = $position;

            if ($before === $after) {
                return;
            }

            $liturgyElement->update(['order' => 65535]);

            $elementsToShift = $this->service
                ->liturgyElements()
                ->whereBetween('order', [
                    min($before, $after),
                    max($before, $after),
                ]);

            $shiftUp = $before < $after;

            $shiftUp
                ? $elementsToShift->decrement('order')
                : $elementsToShift->increment('order');

            $liturgyElement->update(['order' => $after]);
        });

        $this->loadService();
        Flux::toast(variant: 'success', text: 'Service reordered.');
    }

    /**
     * Insert a new element directly after the given anchor element. The anchor
     * may be a section header (in which case the new row appears as the first
     * row beneath that section) or another element.
     */
    #[On('add-element-after')]
    public function addElementAfter(int $afterId, string $type): void
    {
        $anchor = $this->service->liturgyElements()->findOrFail($afterId);
        $newType = LiturgyElementType::from($type);

        DB::transaction(function () use ($anchor, $newType) {
            $this->service
                ->liturgyElements()
                ->where('order', '>', $anchor->order)
                ->increment('order');

            $this->service->liturgyElements()->create([
                'type' => $newType,
                'name' => $newType->label(),
                'order' => $anchor->order + 1,
            ]);
        });

        $this->loadService();
        Flux::toast(variant: 'success', text: $newType->label().' added.');
    }

    public function delete($id): void
    {
        LiturgyElement::findOrFail($id)->delete();
        Flux::modal('delete-element')->close();
        Flux::toast(variant: 'danger', text: 'Liturgy element deleted.');
        $this->loadService();
    }
};
?>

<div x-sort="$wire.sort($item, $position)" x-sort:config="{ handle: '[x-sort-handle]' }" class="mt-2">
    @php($rows = $this->layout())

    @if (empty($rows))
        <div class="rounded-lg border border-dashed border-zinc-300 px-6 py-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            No service elements yet.
        </div>
    @else
        @foreach ($rows as $row)
            @livewire(
                $row['element']->type->component(),
                [
                    'element' => $row['element'],
                    'users' => $this->users,
                    'recentAssigneeIds' => $this->recentAssigneeIds,
                    'sectionColor' => $row['section_color'],
                    'sectionIndex' => $row['section_index'],
                    'sectionElementCount' => $row['section_element_count'],
                    'isFirstInSection' => $row['is_first_in_section'],
                    'isLastInSection' => $row['is_last_in_section'],
                ],
                key('liturgy-element-'.$row['element']->id)
            )

            <x-service.add-element-slot :after-id="$row['element']->id" />
        @endforeach
    @endif
</div>
