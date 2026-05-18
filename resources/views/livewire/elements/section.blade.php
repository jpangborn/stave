<?php

use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Support\SectionTone;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    public LiturgyElement $element;

    #[Locked]
    public Collection $users;

    /** @var array<int, int> */
    #[Locked]
    public array $recentAssigneeIds = [];

    public ?string $sectionColor = null;

    public ?int $sectionIndex = null;

    public ?int $sectionElementCount = null;

    public bool $isFirstInSection = false;

    public bool $isLastInSection = false;

    public string $name;

    public ?string $description;

    public bool $addOpen = false;

    public function mount(?Collection $users = null): void
    {
        $this->users = $users ?? collect();
        $this->name = $this->element->name;
        $this->description = $this->element->description;
    }

    public function updatedName(string $value): void
    {
        $this->element->update(['name' => $value]);
        $this->dispatch('service-element-changed');
        Flux::toast(variant: 'success', text: 'Section renamed.');
    }

    public function updatedDescription(?string $value): void
    {
        $this->element->update(['description' => $value]);
        $this->dispatch('service-element-changed');
        Flux::toast(variant: 'success', text: 'Section description saved.');
    }

    public function recolor(string $color): void
    {
        $this->element->update(['section_color' => $color]);
        $this->sectionColor = $color;
        $this->dispatch('service-element-changed');
        Flux::toast(variant: 'success', text: 'Section color updated.');
    }

    public function delete(): void
    {
        $this->modal('delete-element')->show();
    }

    public function addElement(string $type): void
    {
        $this->addOpen = false;
        $this->dispatch('add-element-after', afterId: $this->element->id, type: $type);
    }
};
?>

@php
    $tone = SectionTone::classesFor($sectionColor);
    $countLabel = $sectionElementCount === 1 ? '1 element' : ($sectionElementCount ?? 0).' elements';
@endphp

<div :x-sort:item="$element->id" wire:key="section-{{ $element->id }}"
     class="group mt-6 grid grid-cols-[4px_1fr_auto] items-stretch gap-4">

    <div class="rounded-sm {{ $tone['stripe'] }}"></div>

    <div class="min-w-0 py-0.5">
        <div class="flex items-baseline gap-3">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] tabular-nums {{ $tone['dot'] }}">
                {{ str_pad((string) ($sectionIndex ?? 0), 2, '0', STR_PAD_LEFT) }}
            </div>

            <h2 class="m-0 min-w-0 flex-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                <x-service.inline-text
                    wire-model="name"
                    :value="$name"
                    placeholder="Untitled section"
                    class="text-2xl font-bold tracking-tight"
                />
            </h2>

            <div class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                {{ $countLabel }}
            </div>
        </div>

        <div class="mt-1 max-w-[680px] text-[12.5px] leading-relaxed text-zinc-600 dark:text-zinc-400">
            <x-service.inline-text
                wire-model="description"
                :value="$description"
                placeholder="Add a section description…"
                class="text-[12.5px] leading-relaxed"
            />
        </div>
    </div>

    <div class="flex items-start gap-1 pt-0.5">
        <div x-sort-handle class="hidden cursor-grab items-center text-zinc-300 group-hover:flex dark:text-zinc-600" title="Drag to reorder">
            <flux:icon name="bars-2" class="size-4" />
        </div>

        <div class="relative" x-data>
            <button type="button" wire:click="$set('addOpen', true)"
                    class="flex size-7 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    title="Add element to this section">
                <flux:icon name="plus" class="size-3.5" />
            </button>

            @if ($addOpen)
                <div wire:click="$set('addOpen', false)" class="fixed inset-0 z-40 bg-black/5"></div>
                <div class="absolute right-0 top-[calc(100%+4px)] z-50 w-44 overflow-hidden rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                     x-data @keydown.escape.window="$wire.set('addOpen', false)">
                    @foreach (LiturgyElementType::cases() as $case)
                        @continue($case === LiturgyElementType::SECTION)
                        <button type="button" wire:click="addElement('{{ $case->value }}')"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[13px] text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                            <flux:icon name="{{ $case->icon() }}" class="size-3.5 text-zinc-500" />
                            {{ $case->label() }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />
            <flux:menu class="min-w-44">
                <flux:menu.submenu heading="Change color" icon="swatch">
                    @foreach (SectionTone::PALETTE as $color)
                        <flux:menu.item wire:click="recolor('{{ $color }}')">
                            <span class="flex items-center gap-2">
                                <span class="size-3 rounded-full {{ SectionTone::classesFor($color)['stripe'] }}"></span>
                                <span class="capitalize">{{ $color }}</span>
                                @if ($color === $sectionColor)
                                    <flux:icon name="check" class="ml-auto size-3 text-emerald-600" />
                                @endif
                            </span>
                        </flux:menu.item>
                    @endforeach
                </flux:menu.submenu>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    <flux:modal name="delete-element" class="min-w-[22rem]">
        <form wire:submit="$parent.delete({{ $element->id }})" class="space-y-6">
            <div>
                <flux:heading size="lg">Delete section?</flux:heading>
                <flux:subheading>
                    <p>This will permanently delete the section. Elements inside it will remain.</p>
                </flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">Delete Section</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
