@props([
    'element',
    'tone',
    'sectionColor' => null,
    'isFirstInSection' => false,
    'isLastInSection' => false,
    'name',
    'placeholder' => null,
    'icon' => null,
    'typeLabel' => null,
    'wireKeyPrefix' => 'row',
])

@php
    $showStripe = $sectionColor !== null;
    $iconName = $icon ?? $element->type->icon();
    $label = $typeLabel ?? $element->type->label();
    $placeholderText = $placeholder ?? $label;

    $stripePosClass = match (true) {
        $isFirstInSection && $isLastInSection => 'top-0.5 bottom-0.5',
        $isFirstInSection => 'top-0.5 bottom-0',
        $isLastInSection => 'top-0 bottom-0.5',
        default => 'inset-y-0',
    };
@endphp

<div :x-sort:item="$element->id"
     wire:key="{{ $wireKeyPrefix }}-{{ $element->id }}"
     class="group relative grid grid-cols-[18px_36px_minmax(160px,1fr)_minmax(120px,200px)_minmax(160px,260px)_32px] items-center gap-3 px-2.5 py-2 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-900/50">

    @if ($showStripe)
        <div class="absolute left-1.5 w-0.5 rounded-sm opacity-70 transition-opacity group-hover:opacity-100 {{ $stripePosClass }} {{ $tone['stripe'] }}"></div>
    @endif

    <div x-sort-handle
         class="flex h-6 cursor-grab items-center justify-center text-zinc-300 opacity-0 transition-opacity group-hover:opacity-100 dark:text-zinc-600"
         title="Drag to reorder">
        <flux:icon name="grip-vertical" class="size-3.5" />
    </div>

    <div class="flex size-9 items-center justify-center rounded-lg {{ $tone['swatch'] }}">
        <flux:icon name="{{ $iconName }}" class="size-4" />
    </div>

    <div class="min-w-0">
        <div class="text-[13.5px] font-semibold text-zinc-900 dark:text-zinc-100">
            <x-service.inline-text
                wire-model="name"
                :value="$name"
                :placeholder="$placeholderText"
                class="text-[13.5px] font-semibold"
            />
        </div>
        <div class="text-[10.5px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            {{ $label }}
        </div>
    </div>

    {{ $assignee }}

    {{ $content }}

    {{ $actions }}
</div>
