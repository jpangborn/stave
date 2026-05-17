@php
    /** @var \App\Models\Group $group */
    /** @var \Illuminate\Support\Collection $availableUsers */
    /** @var \Illuminate\Support\Collection $pickedUsers */
    /** @var string $memberSearch */
    /** @var bool $addMemberOpen */
@endphp

<div class="relative" style="margin-left: auto;">
    <flux:button type="button" variant="primary" icon="user-plus" wire:click="openAddMember">Add member</flux:button>

    @if ($addMemberOpen)
        {{-- Scrim --}}
        <div wire:click="closeAddMember" class="fixed inset-0 z-[40] bg-black/10"></div>

        {{-- Popover --}}
        <div
            x-data
            @keydown.escape.window="$wire.closeAddMember()"
            class="absolute right-0 top-[calc(100%+8px)] z-[50] overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg"
            style="width: min(420px, calc(100vw - 32px));">

            {{-- Header --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-4 pt-3.5 pb-2.5">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Add members to {{ $group->name }}</div>
                    <button
                        type="button"
                        wire:click="closeAddMember"
                        class="flex size-6 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Pick from your congregation. New members join as Member.
                </div>

                {{-- Chip input --}}
                <div
                    x-data
                    @click="$refs.searchInput.focus()"
                    class="mt-2.5 flex min-h-[38px] cursor-text flex-wrap items-center gap-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1.5">

                    @foreach ($pickedUsers as $picked)
                        <span wire:key="picked-chip-{{ $picked->id }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 dark:bg-purple-900/40 pl-1 pr-1 py-0.5 text-xs font-semibold text-purple-700 dark:text-purple-200">
                            <img src="{{ $picked->gravatar }}" alt="{{ $picked->name }}" class="size-5 rounded-full object-cover" />
                            <span>{{ $picked->name }}</span>
                            <button
                                type="button"
                                wire:click="unpickUser({{ $picked->id }})"
                                class="flex size-4 items-center justify-center rounded-full hover:bg-purple-200 dark:hover:bg-purple-800/60">
                                <flux:icon.x-mark class="size-2.5" />
                            </button>
                        </span>
                    @endforeach

                    <input
                        x-ref="searchInput"
                        x-init="$el.focus()"
                        type="text"
                        wire:model.live.debounce.300ms="memberSearch"
                        @if ($pickedUsers->isNotEmpty())
                            @keydown.backspace="if ($event.target.value === '') $wire.unpickUser({{ $pickedUsers->last()->id }})"
                        @endif
                        placeholder="{{ $pickedUsers->isEmpty() ? 'Search by name or email…' : '' }}"
                        class="h-6 min-w-[140px] flex-1 border-0 bg-transparent text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-0"
                    />
                </div>
            </div>

            {{-- Results --}}
            <div class="max-h-[280px] overflow-y-auto p-1.5">
                @if ($availableUsers->isEmpty())
                    <div class="px-3 py-6 text-center text-[13px] text-zinc-500 dark:text-zinc-400">
                        @if (strlen($memberSearch) < 2)
                            {{ $pickedUsers->isNotEmpty() ? 'All set — anyone else?' : 'Search by name or email to find people.' }}
                        @else
                            No people match.
                        @endif
                    </div>
                @else
                    @foreach ($availableUsers as $user)
                        <button
                            type="button"
                            wire:key="avail-{{ $user->id }}"
                            wire:click="pickUser({{ $user->id }})"
                            class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <img src="{{ $user->gravatar }}" alt="{{ $user->name }}" class="size-8 rounded-full object-cover" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                            </div>
                            <div class="flex size-[22px] items-center justify-center rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-400">
                                <flux:icon.plus class="size-3" />
                            </div>
                        </button>
                    @endforeach
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between gap-2 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/60 px-3 py-2.5">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    @if ($pickedUsers->isEmpty())
                        No one selected
                    @elseif ($pickedUsers->count() === 1)
                        1 person selected
                    @else
                        {{ $pickedUsers->count() }} people selected
                    @endif
                </div>
                <div class="flex gap-2">
                    <flux:button type="button" variant="ghost" size="sm" wire:click="closeAddMember">Cancel</flux:button>
                    <flux:button
                        type="button"
                        variant="primary"
                        size="sm"
                        icon="user-plus"
                        :disabled="$pickedUsers->isEmpty()"
                        wire:click="confirmAddMembers">
                        @if ($pickedUsers->count() > 1)
                            Add {{ $pickedUsers->count() }} members
                        @else
                            Add member
                        @endif
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
