@props([
    'label',
    'value',
    'warn' => false,
])

@php
    $toneClass = $warn
        ? 'text-orange-700 dark:text-orange-300'
        : 'text-zinc-900 dark:text-zinc-100';
@endphp

<div class="flex flex-col">
    <div class="flex items-baseline gap-1.5">
        <div class="text-lg font-bold leading-none tracking-tight tabular-nums {{ $toneClass }}">
            {{ $value }}
        </div>
        @if ($warn && $value > 0)
            <div class="size-1.5 rounded-full bg-orange-500 dark:bg-orange-400"></div>
        @endif
    </div>
    <div class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
        {{ $label }}
    </div>
</div>
