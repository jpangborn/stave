@php
    /** @var \App\Models\LiturgyElement $element */
    /** @var \Illuminate\Support\Collection $items */
    /** @var string $variant */  // 'song' | 'reading'
    /** @var bool $open */
    /** @var string $search */
    $selected = $element->content;
    $icon = $variant === 'song' ? 'musical-note' : 'book-open-text';
    $placeholder = $variant === 'song' ? 'Pick a song' : 'Pick a reading';
    $searchPlaceholder = $variant === 'song' ? 'Search songs…' : 'Search readings…';
    $titleField = $variant === 'song' ? 'name' : 'title';
@endphp

<div class="relative w-full" x-data
     @keydown.escape.window="$wire.set('contentOpen', false)">
    <button type="button"
            wire:click="$set('contentOpen', true)"
            class="flex h-8 w-full items-center gap-2 rounded-full px-3 text-left text-[12.5px] transition
                   {{ $selected ? 'hover:bg-zinc-100 dark:hover:bg-zinc-800' : 'border border-dashed border-zinc-300 dark:border-zinc-600' }}
                   {{ $open ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
        <flux:icon name="{{ $icon }}" class="size-3 shrink-0 text-zinc-500" />
        @if ($selected)
            <span class="min-w-0 flex-1 truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $selected->{$titleField} }}</span>
        @else
            <span class="flex-1 italic text-zinc-500 dark:text-zinc-400">{{ $placeholder }}</span>
        @endif
    </button>

    @if ($open)
        <div wire:click="$set('contentOpen', false)" class="fixed inset-0 z-40 bg-black/5"></div>
        <div class="absolute left-0 top-[calc(100%+4px)] z-50 w-80 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <input type="text"
                       x-init="$el.focus()"
                       wire:model.live.debounce.300ms="contentSearch"
                       placeholder="{{ $searchPlaceholder }}"
                       class="h-7 w-full border-0 bg-transparent text-[13px] text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100" />
            </div>

            <div class="max-h-72 overflow-y-auto p-1">
                @if ($selected)
                    <button type="button" wire:click="setContent(null)"
                            class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-[12.5px] text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        <flux:icon name="x-mark" class="size-3 text-zinc-500" />
                        <span class="italic">Clear</span>
                    </button>
                    <div class="my-1 h-px bg-zinc-100 dark:bg-zinc-800"></div>
                @endif

                @if ($items->isEmpty())
                    <div class="px-3 py-6 text-center text-[12.5px] text-zinc-500">
                        @if ($search === '')
                            Start typing to search.
                        @else
                            No {{ $variant === 'song' ? 'songs' : 'readings' }} match.
                        @endif
                    </div>
                @else
                    @foreach ($items as $item)
                        <button type="button" wire:key="content-{{ $item->id }}" wire:click="setContent({{ $item->id }})"
                                class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-[12.5px] hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <flux:icon name="{{ $icon }}" class="size-3 shrink-0 text-zinc-500" />
                            <span class="truncate text-zinc-900 dark:text-zinc-100">{{ $item->{$titleField} }}</span>
                        </button>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</div>
