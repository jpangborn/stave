@props(['label'])

<div {{ $attributes->merge(['class' => 'my-2 flex items-center gap-3.5 px-2 py-3']) }} data-test="day-divider">
    <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ $label }}</span>
    <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
</div>
