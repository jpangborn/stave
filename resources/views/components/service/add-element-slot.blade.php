@props([
    'afterId',
])

@php
    $types = \App\Enums\LiturgyElementType::cases();
@endphp

<div x-data="{ open: false, hov: false }"
     @mouseenter="hov = true"
     @mouseleave="if (!open) hov = false"
     class="relative h-2 cursor-pointer"
     :class="(hov || open) ? 'h-7' : 'h-2'"
     style="transition: height 120ms ease;">

    <div x-show="hov || open" x-cloak
         class="absolute inset-x-0 top-1/2 flex -translate-y-1/2 justify-center">
        <button type="button"
                @click.stop="open = true"
                class="inline-flex items-center gap-1.5 rounded-full border border-dashed border-zinc-300 bg-white px-3 py-1 text-xs font-medium text-zinc-500 hover:border-zinc-400 hover:text-zinc-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200">
            <flux:icon name="plus" class="size-3" />
            Add element
        </button>
    </div>

    <template x-if="open">
        <div>
            <div @click.stop="open = false; hov = false" class="fixed inset-0 z-40 bg-black/5"></div>
            <div class="absolute left-1/2 top-full z-50 mt-2 w-44 -translate-x-1/2 overflow-hidden rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                 @click.stop
                 @keydown.escape.window="open = false; hov = false">
                @foreach ($types as $case)
                    <button type="button"
                            @click="$wire.call('addElementAfter', {{ $afterId }}, '{{ $case->value }}'); open = false; hov = false"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[13px] text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        <flux:icon name="{{ $case->icon() }}" class="size-3.5 text-zinc-500" />
                        {{ $case->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    </template>
</div>
