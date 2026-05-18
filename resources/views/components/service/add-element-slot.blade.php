@props([
    'afterId',
])

@php
    $types = \App\Enums\LiturgyElementType::cases();
@endphp

<div x-data="{ open: false, hov: false }"
     @mouseenter="hov = true"
     @mouseleave="if (!open) hov = false"
     class="relative z-10 h-2.5 cursor-pointer">

    <button type="button"
            x-show="hov || open"
            x-cloak
            @click.stop="open = true"
            class="absolute left-1/2 top-1/2 z-10 inline-flex -translate-x-1/2 -translate-y-1/2 items-center gap-1.5 rounded-full border border-dashed border-zinc-300 bg-white px-3 py-1 text-[11.5px] font-medium text-zinc-500 shadow-[0_1px_2px_rgba(0,0,0,0.04)] hover:border-zinc-400 hover:text-zinc-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200">
        <flux:icon name="plus" class="size-3" />
        Add element
    </button>

    <template x-if="open">
        <div>
            <div @click.stop="open = false; hov = false" class="fixed inset-0 z-40 bg-black/5"></div>
            <div class="absolute left-1/2 top-full z-50 mt-2 w-48 -translate-x-1/2 overflow-hidden rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                 @click.stop
                 @keydown.escape.window="open = false; hov = false">
                <div class="px-2 pt-1.5 pb-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                    Add element
                </div>
                @foreach ($types as $case)
                    <button type="button"
                            @click="$wire.call('addElementAfter', {{ $afterId }}, '{{ $case->value }}'); open = false; hov = false"
                            class="flex w-full items-center gap-2.5 rounded-md px-2 py-1.5 text-left text-[13px] text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                            <flux:icon name="{{ $case->icon() }}" class="size-3.5" />
                        </span>
                        {{ $case->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    </template>
</div>
