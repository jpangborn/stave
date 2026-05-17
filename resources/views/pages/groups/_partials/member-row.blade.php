@php
    use App\Enums\GroupRole;

    /** @var \App\Models\User $user */
    /** @var bool $isLeader */
    /** @var bool $isOnlyLeader */

    $userIsLeader = $user->pivot->role === GroupRole::LEADER;
    $isSelf = $user->id === auth()->id();
@endphp

<div
    wire:key="member-{{ $user->id }}"
    class="group grid items-center gap-4 border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
    style="grid-template-columns: 1fr 180px 160px 44px;">

    {{-- Member --}}
    <div class="flex items-center gap-3 min-w-0">
        <flux:avatar :name="$user->name" :src="$user->gravatar" size="md" />
        <div class="min-w-0">
            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
        </div>
    </div>

    {{-- Role --}}
    <div>
        @if ($isLeader && ! $isSelf)
            <flux:dropdown align="start" offset="4">
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :icon="$userIsLeader ? 'star' : 'user'"
                    icon:trailing="chevron-down"
                    :disabled="$isOnlyLeader"
                    :title="$isOnlyLeader ? 'A group must have at least one leader.' : null"
                    class="!justify-start !font-semibold {{ $userIsLeader ? '!text-purple-600 dark:!text-purple-300' : '' }}">
                    {{ $userIsLeader ? 'Leader' : 'Member' }}
                </flux:button>

                <flux:menu class="min-w-[15rem]">
                    <flux:menu.item
                        icon="star"
                        wire:click="setMemberRole({{ $user->id }}, 'leader')"
                        :disabled="$userIsLeader">
                        <div class="flex flex-col items-start">
                            <span class="text-sm font-semibold">Leader</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Can manage members, pin and delete conversations.</span>
                        </div>
                    </flux:menu.item>

                    <flux:menu.item
                        icon="user"
                        wire:click="setMemberRole({{ $user->id }}, 'member')"
                        :disabled="! $userIsLeader || $isOnlyLeader"
                        :title="$isOnlyLeader ? 'A group must have at least one leader.' : null">
                        <div class="flex flex-col items-start">
                            <span class="text-sm font-semibold">Member</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Can read and post in conversations.</span>
                        </div>
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @else
            @if ($userIsLeader)
                <flux:badge size="sm" color="purple" icon="star">Leader</flux:badge>
            @else
                <flux:badge size="sm" color="zinc">Member</flux:badge>
            @endif
        @endif
    </div>

    {{-- Joined --}}
    <div class="whitespace-nowrap text-[13px] text-zinc-500 dark:text-zinc-400">
        {{ $user->pivot->created_at->diffForHumans() }}
    </div>

    {{-- Actions --}}
    <div class="flex justify-end">
        @if ($isLeader && ! $isSelf)
            <flux:dropdown align="end" offset="-8" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 data-[flux-dropdown-open]:opacity-100 transition-opacity">
                <flux:button type="button" variant="ghost" size="sm" square icon="ellipsis-horizontal" />

                <flux:menu class="min-w-48">
                    @if ($userIsLeader)
                        <flux:menu.item
                            icon="user"
                            wire:click="setMemberRole({{ $user->id }}, 'member')"
                            :disabled="$isOnlyLeader"
                            :title="$isOnlyLeader ? 'A group must have at least one leader.' : null">
                            Change to member
                        </flux:menu.item>
                    @else
                        <flux:menu.item icon="star" wire:click="setMemberRole({{ $user->id }}, 'leader')">
                            Make leader
                        </flux:menu.item>
                    @endif

                    <flux:menu.separator />

                    <flux:menu.item
                        icon="trash"
                        variant="danger"
                        wire:click="removeMember({{ $user->id }})"
                        wire:confirm="Remove {{ $user->name }} from this group?"
                        :disabled="$isOnlyLeader"
                        :title="$isOnlyLeader ? 'A group must have at least one leader.' : null">
                        Remove from group
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>
</div>
