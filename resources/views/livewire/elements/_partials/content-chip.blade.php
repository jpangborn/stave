@php
    /** @var \App\Models\LiturgyElement $element */
    /** @var \Illuminate\Support\Collection $items */
    /** @var string $variant */  // 'song' | 'reading'
    /** @var bool $open */
    /** @var string $search */
    /** @var int|null $hoverId */
    /** @var \App\Models\Song|\App\Models\Reading|null $previewItem */
    $selected = $element->content;
    $icon = $variant === 'song' ? 'musical-note' : 'book-open-text';
    $placeholder = $variant === 'song' ? 'Pick a song' : 'Pick a reading';
    $searchPlaceholder = $variant === 'song' ? 'Search songs…' : 'Search scripture and readings…';
    $titleField = $variant === 'song' ? 'name' : 'title';
    $addNewLabel = $variant === 'song' ? 'Add new song' : 'Add new reading';
    $emptyHint = $variant === 'song' ? 'No songs match.' : 'No readings match.';

    $previewTitle = $previewItem?->{$titleField};
    $previewBody = $previewItem
        ? ($variant === 'song' ? ($previewItem->lyrics ?? '') : ($previewItem->text ?? ''))
        : '';
@endphp

<div class="relative w-full" x-data
     @keydown.escape.window="$wire.set('contentOpen', false)">
    <button type="button"
            wire:click="openContent"
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
        <div class="absolute right-0 top-[calc(100%+4px)] z-50 w-[600px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <input type="text"
                       x-init="$el.focus()"
                       wire:model.live.debounce.300ms="contentSearch"
                       placeholder="{{ $searchPlaceholder }}"
                       class="h-7 w-full border-0 bg-transparent text-[13px] text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100" />
            </div>

            <div class="grid grid-cols-[260px_1fr]" style="height: 340px;">
                <div class="overflow-y-auto border-r border-zinc-200 dark:border-zinc-700">
                    @if ($items->isEmpty())
                        <div class="px-3 py-6 text-center text-[12.5px] text-zinc-500">{{ $emptyHint }}</div>
                    @else
                        @foreach ($items as $item)
                            @php $isActive = $selected?->id === $item->id; @endphp
                            <button type="button"
                                    wire:key="content-{{ $item->id }}"
                                    wire:click="setContent({{ $item->id }})"
                                    wire:mouseenter="$set('hoverContentId', {{ $item->id }})"
                                    class="flex w-full items-start gap-2 border-l-2 px-3 py-2 text-left transition
                                           {{ $isActive ? 'border-l-emerald-600 bg-emerald-50/40 dark:border-l-emerald-400 dark:bg-emerald-900/20' : 'border-l-transparent hover:bg-zinc-100 dark:hover:bg-zinc-800' }}
                                           {{ $hoverId === $item->id && ! $isActive ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                                <flux:icon name="{{ $icon }}" class="mt-0.5 size-3 shrink-0 text-zinc-500" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate text-[12.5px] {{ $isActive ? 'font-semibold text-emerald-700 dark:text-emerald-300' : 'font-medium text-zinc-900 dark:text-zinc-100' }}">
                                            {{ $item->{$titleField} }}
                                        </span>
                                        @if ($isActive)
                                            <flux:icon name="check" class="ml-auto size-3 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                        @endif
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-2 text-[10px] text-zinc-500 dark:text-zinc-400">
                                        @if ($variant === 'song')
                                            @if (! empty($item->ccli_number))
                                                <span>CCLI {{ $item->ccli_number }}</span>
                                            @endif
                                        @else
                                            <span class="capitalize">{{ $item->type?->value }}</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    @endif
                </div>

                <div class="overflow-y-auto bg-zinc-50 px-5 py-4 dark:bg-zinc-950/30">
                    @if ($previewItem)
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="text-[14px] font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                {{ $previewTitle }}
                            </div>
                            <div class="text-[9.5px] font-bold uppercase tracking-[0.08em] text-zinc-400">Preview</div>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-0.5 text-[10.5px] text-zinc-500 dark:text-zinc-400">
                            @if ($variant === 'song')
                                @if (! empty($previewItem->ccli_number))
                                    <span>CCLI {{ $previewItem->ccli_number }}</span>
                                @endif
                                @if (! empty($previewItem->authors))
                                    <span class="truncate">{{ $previewItem->authors }}</span>
                                @endif
                            @else
                                <span class="capitalize">{{ $previewItem->type?->value }}</span>
                            @endif
                            @if ($previewItem->last_used_date)
                                <span class="ml-auto">Last used {{ $previewItem->last_used_date->format('M j, Y') }}</span>
                            @endif
                        </div>
                        <div class="mt-2.5 text-[12px] leading-relaxed text-zinc-700 dark:text-zinc-300
                                    {{ $variant === 'reading' ? 'italic' : '' }}
                                    [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_em]:italic [&_h1]:mt-2 [&_h1]:mb-1 [&_h1]:text-[13px] [&_h1]:font-semibold [&_h2]:mt-2 [&_h2]:mb-1 [&_h2]:text-[12.5px] [&_h2]:font-semibold">
                            @if (trim(strip_tags($previewBody)) === '')
                                <div class="italic text-zinc-400">No preview available.</div>
                            @else
                                {!! $previewBody !!}
                            @endif
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center text-[12px] italic text-zinc-400">
                            Hover an item to preview.
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 border-t border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    @if ($selected)
                        <button type="button" wire:click="setContent(null)"
                                class="inline-flex items-center gap-1.5 text-[11.5px] text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                            <flux:icon name="x-mark" class="size-3" />
                            Remove
                        </button>
                    @endif
                    @if ($variant === 'reading')
                        <a href="{{ route('readings.create') }}"
                           class="inline-flex items-center gap-1.5 text-[11.5px] text-zinc-500 no-underline hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                            <flux:icon name="plus" class="size-3" />
                            {{ $addNewLabel }}
                        </a>
                    @endif
                </div>
                <div class="inline-flex items-center gap-1 text-[10.5px] text-zinc-400">
                    <kbd class="rounded border border-zinc-200 bg-white px-1 py-0.5 font-mono text-[9.5px] dark:border-zinc-700 dark:bg-zinc-900">↵</kbd>
                    select
                </div>
            </div>
        </div>
    @endif
</div>
