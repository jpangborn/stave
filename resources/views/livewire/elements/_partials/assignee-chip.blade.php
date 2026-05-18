@php
    /** @var \App\Models\LiturgyElement $element */
    /** @var \Illuminate\Support\Collection $users */
    /** @var array<int,int> $recentIds */
    /** @var bool $open */
    /** @var string $search */
    $assignee = $users->firstWhere('id', $element->assignee_id);
    $recents = $users->whereIn('id', $recentIds)->where('id', '!=', $element->assignee_id);
    $rest = $users->whereNotIn('id', $recentIds)->where('id', '!=', $element->assignee_id);
    if ($search !== '') {
        $haystack = strtolower($search);
        $filter = fn ($u) => str_contains(strtolower($u->name), $haystack);
        $recents = $recents->filter($filter);
        $rest = $rest->filter($filter);
    }
@endphp

<div class="relative w-full" x-data
     @keydown.escape.window="$wire.set('assigneeOpen', false)">
    <button type="button"
            wire:click="$set('assigneeOpen', true)"
            class="flex h-8 w-full items-center gap-2 rounded-full px-2.5 text-left text-[12.5px] transition hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $open ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
        @if ($assignee)
            <flux:avatar :name="$assignee->name" :src="$assignee->gravatarUrl()" size="xs" />
            <span class="min-w-0 flex-1 truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $assignee->name }}</span>
        @else
            <span class="flex size-6 shrink-0 items-center justify-center rounded-sm border border-dashed border-zinc-300 text-zinc-400 dark:border-zinc-600 dark:text-zinc-500">
                <flux:icon name="user" class="size-3" />
            </span>
            <span class="flex-1 italic text-zinc-500 dark:text-zinc-400">Unassigned</span>
        @endif
    </button>

    @if ($open)
        <div wire:click="$set('assigneeOpen', false)" class="fixed inset-0 z-40 bg-black/5"></div>
        <div class="absolute left-0 top-[calc(100%+4px)] z-50 w-72 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <input type="text"
                       x-init="$el.focus()"
                       wire:model.live.debounce.200ms="assigneeSearch"
                       placeholder="Search people…"
                       class="h-7 w-full border-0 bg-transparent text-[13px] text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100" />
            </div>

            <div class="max-h-72 overflow-y-auto p-1">
                @if ($assignee)
                    <button type="button" wire:click="setAssignee(null)"
                            class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-[12.5px] text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                            <flux:icon name="x-mark" class="size-3" />
                        </span>
                        <span class="italic">Unassign</span>
                    </button>
                    <div class="my-1 h-px bg-zinc-100 dark:bg-zinc-800"></div>
                @endif

                @if ($recents->isNotEmpty())
                    <div class="px-2 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">In this service</div>
                    @foreach ($recents as $user)
                        <button type="button" wire:key="recent-{{ $user->id }}" wire:click="setAssignee({{ $user->id }})"
                                class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-[12.5px] hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <flux:avatar :name="$user->name" :src="$user->gravatarUrl()" size="xs" />
                            <span class="truncate text-zinc-900 dark:text-zinc-100">{{ $user->name }}</span>
                        </button>
                    @endforeach
                    @if ($rest->isNotEmpty())
                        <div class="my-1 h-px bg-zinc-100 dark:bg-zinc-800"></div>
                    @endif
                @endif

                @if ($rest->isNotEmpty())
                    @if ($recents->isEmpty() && $search === '')
                        <div class="px-2 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">People</div>
                    @endif
                    @foreach ($rest as $user)
                        <button type="button" wire:key="all-{{ $user->id }}" wire:click="setAssignee({{ $user->id }})"
                                class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-[12.5px] hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <flux:avatar :name="$user->name" :src="$user->gravatarUrl()" size="xs" />
                            <span class="truncate text-zinc-900 dark:text-zinc-100">{{ $user->name }}</span>
                        </button>
                    @endforeach
                @endif

                @if ($recents->isEmpty() && $rest->isEmpty())
                    <div class="px-3 py-6 text-center text-[12.5px] text-zinc-500">No people match.</div>
                @endif
            </div>
        </div>
    @endif
</div>
