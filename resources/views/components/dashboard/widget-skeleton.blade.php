@props(['rows' => 3])

<flux:card class="!px-4.5 !py-4 animate-pulse">
    <div class="mb-2 flex items-center gap-2">
        <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800"></div>
        <div class="h-3.5 w-28 rounded bg-zinc-100 dark:bg-zinc-800"></div>
    </div>

    <div class="mt-4 space-y-3">
        @for ($i = 0; $i < $rows; $i++)
            <div
                class="h-3 rounded bg-zinc-100 dark:bg-zinc-800"
                style="width: {{ [90, 70, 80, 60][$i % 4] }}%"
            ></div>
        @endfor
    </div>
</flux:card>
