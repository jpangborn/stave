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
    $indexLabel = str_pad((string) ($sectionIndex ?? 0), 2, '0', STR_PAD_LEFT);
@endphp

<div :x-sort:item="$element->id" wire:key="section-{{ $element->id }}"
     class="group relative mt-6 mb-2 overflow-hidden rounded-lg pl-[22px] pr-[18px] pt-4 pb-4 {{ $tone['soft'] }}">

    <div class="absolute inset-y-0 left-0 w-1 {{ $tone['stripe'] }}"></div>

    <div class="flex items-start gap-4">
        <div class="min-w-[28px] shrink-0 pt-1 text-[22px] font-bold leading-none tracking-tight tabular-nums {{ $tone['dot'] }}">
            {{ $indexLabel }}
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="m-0 text-xl font-bold leading-tight tracking-tight text-zinc-900 dark:text-zinc-100">
                <x-service.inline-text
                    wire-model="name"
                    :value="$name"
                    placeholder="Untitled section"
                    class="text-xl font-bold tracking-tight"
                />
            </h2>

            <div class="mt-1 max-w-[720px] text-[12.5px] leading-relaxed text-zinc-600 dark:text-zinc-400">
                <x-service.inline-text
                    wire-model="description"
                    :value="$description"
                    placeholder="Add a section description…"
                    class="text-[12.5px] leading-relaxed"
                />
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 pt-0.5">
            <div class="rounded-full border border-black/5 bg-white/70 px-2.5 py-1 text-[10.5px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-white/5 dark:bg-zinc-900/40 dark:text-zinc-400">
                {{ $countLabel }}
            </div>

            <div class="relative" x-data>
                <button type="button" wire:click="$set('addOpen', true)"
                        class="flex size-7 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        title="Add element to this section">
                    <flux:icon name="plus" class="size-3.5" />
                </button>

                @if ($addOpen)
                    <div wire:click="$set('addOpen', false)" class="fixed inset-0 z-40 bg-black/5"></div>
                    <div class="absolute right-0 top-[calc(100%+4px)] z-50 w-48 overflow-hidden rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                         x-data @keydown.escape.window="$wire.set('addOpen', false)">
                        <div class="px-2 pt-1.5 pb-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                            Add element
                        </div>
                        @foreach (LiturgyElementType::cases() as $case)
                            @continue($case === LiturgyElementType::SECTION)
                            <button type="button" wire:click="addElement('{{ $case->value }}')"
                                    class="flex w-full items-center gap-2.5 rounded-md px-2 py-1.5 text-left text-[13px] text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                    <flux:icon name="{{ $case->icon() }}" class="size-3.5" />
                                </span>
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
