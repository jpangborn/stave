@props([
    'title',
    'icon',
    'chip' => 'zinc',
    'href' => null,
    'linkLabel' => 'View all',
    'isEmpty' => false,
    'emptyMessage' => null,
])

<flux:card class="!px-4.5 !py-4 hover:shadow-sm transition-shadow" {{ $attributes }}>
    <div class="mb-2 flex items-center gap-2">
        <div @class([
            'flex size-7 items-center justify-center rounded-lg',
            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $chip === 'zinc',
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' => $chip === 'emerald',
        ])>
            <flux:icon :icon="$icon" variant="micro" />
        </div>
        <h2 class="flex-1 text-sm font-bold text-zinc-900 dark:text-white">{{ $title }}</h2>
        @if ($href)
            <a
                href="{{ $href }}"
                wire:navigate
                class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                {{ $linkLabel }}
                <flux:icon.arrow-right variant="micro" class="size-3" />
            </a>
        @endif
    </div>

    <div class="mt-3">
        @if ($isEmpty)
            <div class="flex flex-col items-center py-6 text-center">
                <flux:icon.check-circle class="size-6 text-emerald-500" />
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $emptyMessage }}</p>
            </div>
        @else
            {{ $slot }}
        @endif
    </div>
</flux:card>
