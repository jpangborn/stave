@php
    /** @var \App\Models\Group $group */
    $description = $group->description
        ? trim(html_entity_decode(strip_tags((string) $group->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        : null;
@endphp

<div class="flex h-full flex-col overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 transition hover:border-zinc-300 dark:hover:border-white/20 hover:shadow-sm">
    <a href="{{ route('groups.show', $group) }}" wire:navigate class="block">
        <x-groups.cover :group="$group" height="72px"/>
    </a>

    <div class="flex flex-1 flex-col gap-2.5 px-4 pt-3 pb-3.5">
        <div class="flex items-start justify-between gap-2">
            <a href="{{ route('groups.show', $group) }}" wire:navigate class="font-bold text-zinc-900 dark:text-zinc-100 hover:underline">
                {{ $group->name }}
            </a>
            <flux:badge size="sm" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
        </div>

        <p class="text-xs text-zinc-500 leading-normal line-clamp-2 min-h-[34px]">
            {{ $description ?? 'No description.' }}
        </p>

        <div class="flex items-center justify-between gap-3 pt-2 mt-auto border-t border-zinc-100 dark:border-zinc-800">
            <span class="text-xs text-zinc-500">
                <span class="font-semibold text-zinc-900 dark:text-zinc-100 tabular-nums">{{ $group->members_count ?? $group->members()->count() }}</span>
                {{ Str::plural('member', $group->members_count ?? 0) }}
            </span>

            @can('join', $group)
                <flux:button
                    size="sm"
                    variant="ghost"
                    wire:click="join({{ $group->id }})"
                    icon="user-plus"
                >Join</flux:button>
            @endcan
        </div>
    </div>
</div>
