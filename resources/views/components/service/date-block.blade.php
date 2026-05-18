@props([
    'date',
])

<div class="flex w-16 shrink-0 flex-col items-center rounded-lg border border-zinc-200 bg-white py-1.5 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-accent">
        {{ $date->format('M') }}
    </div>
    <div class="mt-0.5 text-[26px] font-bold leading-none tracking-tight text-zinc-900 tabular-nums dark:text-zinc-100">
        {{ $date->format('j') }}
    </div>
    <div class="mt-1 text-[10px] font-medium text-zinc-500 tabular-nums dark:text-zinc-400">
        {{ $date->format('Y') }}
    </div>
</div>
