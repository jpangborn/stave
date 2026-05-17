@props([
    'attachment',
    'compact' => false,
])

@php
    $iconName = match (true) {
        $attachment->isImage() => 'photo',
        $attachment->isAudio() => 'musical-note',
        str_starts_with($attachment->mime_type, 'video/') => 'film',
        $attachment->mime_type === 'text/markdown' => 'document-text',
        default => 'document',
    };
@endphp

<div
    @class([
        'group/attachment flex items-center gap-2.5 rounded-md border border-zinc-200 bg-white px-2.5 py-2 dark:border-zinc-700 dark:bg-zinc-900',
        'text-xs' => $compact,
        'text-sm' => ! $compact,
    ])
    data-test="conversation-attachment"
    wire:key="attachment-{{ $attachment->id }}"
>
    <span @class([
        'grid shrink-0 place-items-center rounded-md text-zinc-500 dark:text-zinc-400',
        'size-7 bg-zinc-100 dark:bg-zinc-800' => ! $compact,
        'size-6 bg-zinc-100 dark:bg-zinc-800' => $compact,
    ])>
        <flux:icon :name="$iconName" variant="micro" />
    </span>
    <a
        href="{{ $attachment->url }}"
        target="_blank"
        rel="noopener noreferrer"
        class="min-w-0 flex-1"
        data-test="conversation-attachment-link"
    >
        <div class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $attachment->original_name }}</div>
        <div class="text-xs text-zinc-500">{{ \Illuminate\Support\Number::fileSize($attachment->size) }}</div>
    </a>
    @can('delete', $attachment)
        <button
            type="button"
            wire:click="deleteAttachment({{ $attachment->id }})"
            wire:confirm="Delete {{ $attachment->original_name }}?"
            class="grid size-7 shrink-0 place-items-center rounded-md text-zinc-400 opacity-0 transition-opacity hover:bg-zinc-100 hover:text-red-600 focus-visible:opacity-100 group-hover/attachment:opacity-100 dark:hover:bg-zinc-800"
            aria-label="Delete attachment"
            data-test="conversation-attachment-delete"
        >
            <flux:icon.trash variant="micro" />
        </button>
    @endcan
</div>
