@php
    /** @var \App\Models\Conversation $conversation */
    /** @var \App\Models\Group $group */
    /** @var bool $isLeader */
    /** @var string $preview */
    /** @var string $previewAuthor */
@endphp

<div
    wire:key="convo-{{ $conversation->id }}"
    class="group relative grid items-center gap-3.5 rounded-lg border-t border-zinc-100 dark:border-zinc-800 px-3.5 py-3.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
    style="grid-template-columns: auto 1fr auto auto;">

    {{-- Avatar with pin indicator --}}
    <div class="relative">
        <flux:avatar
            :name="$conversation->creator?->name ?? '?'"
            :src="$conversation->creator?->gravatar"
            size="md"
        />
        @if ($conversation->isPinned())
            <span
                title="Pinned"
                class="absolute -top-1 -right-1 flex size-[18px] items-center justify-center rounded-full bg-purple-600 text-white ring-2 ring-white dark:ring-zinc-900">
                <flux:icon.bookmark class="size-2.5" />
            </span>
        @endif
    </div>

    {{-- Title + preview --}}
    <div class="min-w-0">
        <div class="flex items-center gap-2 min-w-0">
            <a
                href="{{ route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]) }}"
                wire:navigate
                class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100 hover:underline">
                {{ $conversation->title }}
            </a>
            <flux:badge size="sm" color="zinc" inset="top bottom">
                {{ $conversation->comments_count }} {{ str('reply')->plural($conversation->comments_count) }}
            </flux:badge>
        </div>

        @if ($preview !== '')
            <div class="mt-1 truncate text-[13px] text-zinc-500 dark:text-zinc-400">
                <span class="text-zinc-700 dark:text-zinc-200">{{ $previewAuthor }}:</span>
                {{ $preview }}
            </div>
        @endif
    </div>

    {{-- Timestamp --}}
    <div class="whitespace-nowrap text-xs text-zinc-400 dark:text-zinc-500">
        {{ ($conversation->last_comment_at ?? $conversation->created_at)->diffForHumans() }}
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-0.5" style="min-width: 64px;">
        @if ($isLeader)
            <flux:tooltip :content="$conversation->isPinned() ? 'Unpin' : 'Pin to top'">
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    square
                    icon="bookmark"
                    wire:click="{{ $conversation->isPinned() ? 'unpinConversation' : 'pinConversation' }}({{ $conversation->id }})"
                    class="{{ $conversation->isPinned() ? 'text-purple-600 dark:text-purple-300' : 'opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity' }}"
                />
            </flux:tooltip>

            <flux:dropdown align="end" offset="-8" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 data-[flux-dropdown-open]:opacity-100 transition-opacity">
                <flux:button type="button" variant="ghost" size="sm" square icon="ellipsis-horizontal" />

                <flux:menu class="min-w-48">
                    @if ($conversation->isPinned())
                        <flux:menu.item icon="bookmark-slash" wire:click="unpinConversation({{ $conversation->id }})">Unpin from top</flux:menu.item>
                    @else
                        <flux:menu.item icon="bookmark" wire:click="pinConversation({{ $conversation->id }})">Pin to top</flux:menu.item>
                    @endif
                    <flux:menu.separator />
                    <flux:menu.item icon="trash" variant="danger" wire:click="openDeleteConversation({{ $conversation->id }})">
                        Delete conversation
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @else
            <div class="w-16"></div>
        @endif
    </div>
</div>
