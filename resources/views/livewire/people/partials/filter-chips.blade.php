@props([
    'counts',
    'current' => null,
])

@php
    $items = [
        ['key' => 'all',        'label' => 'All',         'icon' => 'users'],
        ['key' => 'member',     'label' => 'Members',     'icon' => 'home-modern'],
        ['key' => 'catechumen', 'label' => 'Catechumens', 'icon' => 'book-open'],
        ['key' => 'adherent',   'label' => 'Adherents',   'icon' => 'user-group'],
        ['key' => 'visitor',    'label' => 'Visitors',    'icon' => 'face-smile'],
    ];
@endphp

<div class="inline-flex flex-wrap gap-1 p-1 rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
    @foreach ($items as $it)
        @php
            $active = ($it['key'] === 'all' && $current === null) || $current === $it['key'];
        @endphp
        <button
            type="button"
            wire:click="setFilter('{{ $it['key'] }}')"
            @class([
                'inline-flex items-center gap-1.5 px-2.5 h-7 rounded-md text-xs font-medium transition',
                'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-600' => $active,
                'text-zinc-600 dark:text-zinc-300 hover:bg-white/60 dark:hover:bg-zinc-700/60' => ! $active,
            ])
        >
            <flux:icon :name="$it['icon']" variant="micro" class="size-3.5" />
            {{ $it['label'] }}
            <span @class([
                'ml-0.5 text-[11px] tabular-nums',
                'text-zinc-500 dark:text-zinc-300' => $active,
                'text-zinc-400 dark:text-zinc-500' => ! $active,
            ])>{{ $counts[$it['key']] ?? 0 }}</span>
        </button>
    @endforeach
</div>
