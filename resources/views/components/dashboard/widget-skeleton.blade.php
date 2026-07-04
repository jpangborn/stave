@props(['rows' => 3, 'title' => null, 'icon' => null, 'chip' => 'zinc'])

<flux:card class="!px-4.5 !py-4 h-full">
    <div class="mb-2 flex items-center gap-2">
        @if ($title)
            <div @class([
                'flex size-7 items-center justify-center rounded-lg',
                'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $chip === 'zinc',
                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' => $chip === 'emerald',
            ])>
                <flux:icon :icon="$icon" variant="micro" />
            </div>
            <h2 class="flex-1 text-sm font-bold text-zinc-900 dark:text-white">{{ $title }}</h2>
        @else
            <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800"></div>
            <div class="h-3.5 w-28 rounded bg-zinc-100 dark:bg-zinc-800"></div>
        @endif
    </div>

    <div class="mt-4 space-y-3 animate-pulse">
        @for ($i = 0; $i < $rows; $i++)
            <div
                class="h-3 rounded bg-zinc-100 dark:bg-zinc-800"
                style="width: {{ [90, 70, 80, 60][$i % 4] }}%"
            ></div>
        @endfor
    </div>
</flux:card>
