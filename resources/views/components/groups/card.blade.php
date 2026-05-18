@props(['group'])

@php
    /** @var \App\Models\Group $group */
    $latestConversation = $group->latestConversation;
    $latestComment = $latestConversation?->lastComment->first();
    $previewAuthor = $latestComment?->commentator?->name;
    $previewBody = $latestComment
        ? trim(html_entity_decode(strip_tags((string) $latestComment->text), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        : null;
    $membersCount = $group->members_count ?? $group->members()->count();
    $previewMembers = $group->relationLoaded('members') ? $group->members->take(4) : $group->members()->limit(4)->get();
    $extraMembers = max(0, $membersCount - $previewMembers->count());
@endphp

<a
    href="{{ route('groups.show', $group) }}"
    wire:navigate
    class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 rounded-lg"
>
    <div class="flex h-full flex-col overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 transition group-hover:border-zinc-300 dark:group-hover:border-white/20 group-hover:shadow-sm">
        <div class="relative h-[132px]">
            <x-groups.cover :group="$group" height="132px" :show-initial="false" />
            <div
                class="absolute inset-0"
                style="background: linear-gradient(to top, rgba(0,0,0,0.62) 0%, rgba(0,0,0,0.30) 45%, rgba(0,0,0,0) 100%);"
                aria-hidden="true"
            ></div>
            <div class="absolute inset-x-0 bottom-0 flex flex-col gap-2 px-4 pt-3 pb-3">
                <h3 class="text-[17px] font-bold tracking-tight text-white" style="text-shadow: 0 1px 2px rgba(0,0,0,0.35);">
                    {{ $group->name }}
                </h3>
                <div class="flex flex-wrap gap-1.5">
                    <flux:badge size="sm" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
                    <flux:badge size="sm" :color="$group->messaging->color()">{{ $group->messaging->label() }}</flux:badge>
                </div>
            </div>
        </div>

        <div @class([
            'flex-1 min-h-16 px-4 py-2.5 border-t border-zinc-100 dark:border-zinc-800',
            'bg-zinc-50 dark:bg-zinc-800/40' => ! $latestComment,
            'bg-accent/5' => $latestComment,
        ])>
            @if ($latestComment && $previewAuthor)
                <p class="text-xs text-zinc-900 dark:text-zinc-100 line-clamp-2">
                    <span class="font-semibold">{{ $previewAuthor }}:</span>
                    <span class="text-zinc-700 dark:text-zinc-300">{{ $previewBody }}</span>
                </p>
                <p class="mt-1 text-[11px] text-zinc-500">
                    {{ $latestComment->created_at->diffForHumans() }}
                </p>
            @else
                <p class="text-xs italic text-zinc-500 dark:text-zinc-400">No messages yet.</p>
            @endif
        </div>

        <div class="flex items-center justify-between gap-3 px-4 py-2.5 border-t border-zinc-100 dark:border-zinc-800 shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                @if ($previewMembers->isNotEmpty())
                    <flux:avatar.group class="dark:**:ring-zinc-800">
                        @foreach ($previewMembers as $member)
                            <flux:avatar
                                size="xs"
                                :name="$member->name"
                                :src="$member->gravatar"
                            />
                        @endforeach
                        @if ($extraMembers > 0)
                            <flux:avatar size="xs">+{{ $extraMembers }}</flux:avatar>
                        @endif
                    </flux:avatar.group>
                @endif
                <span class="text-xs text-zinc-500 whitespace-nowrap">
                    {{ $membersCount }} {{ Str::plural('member', $membersCount) }}
                </span>
            </div>
            <span class="text-xs font-semibold text-zinc-500 transition-all group-hover:text-accent group-hover:translate-x-0.5 whitespace-nowrap">
                Open <span aria-hidden="true">→</span>
            </span>
        </div>
    </div>
</a>
