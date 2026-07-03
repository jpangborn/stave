<?php

use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    /** @return Collection<int, \App\Models\LiturgyElement> */
    #[Computed]
    public function myAssignments(): Collection
    {
        return auth()->user()->upcomingAssignments();
    }

    /** @return Collection<int, Service> */
    #[Computed]
    public function upcomingServices(): Collection
    {
        return Service::query()->upcoming()->with('template')->limit(3)->get();
    }

    public function relativeDateBadge(Carbon $date): string
    {
        return match (true) {
            $date->isToday() => 'Today',
            $date->isTomorrow() => 'Tomorrow',
            $date->isSunday() && $date->lt(today()->addDays(7)) => 'This Sunday',
            default => 'In '.(int) today()->diffInDays($date).' days',
        };
    }

    /** @return Collection<int, \App\Models\Group> */
    #[Computed]
    public function messageGroups(): Collection
    {
        return auth()->user()->groups()->notDirect()
            ->with('latestConversation.lastComment')
            ->get();
    }

    /** @return Collection<int, int> */
    #[Computed]
    public function groupUnreadCounts(): Collection
    {
        return auth()->user()->unreadGroupCounts();
    }

    /** @return Collection<int, \App\Models\Conversation> */
    #[Computed]
    public function directMessages(): Collection
    {
        return auth()->user()->directConversations()
            ->with(['group.members', 'lastComment.commentator'])
            ->orderByDesc('last_comment_at')
            ->limit(5)
            ->get();
    }

    /** @return Collection<int, int> */
    #[Computed]
    public function directUnreadCounts(): Collection
    {
        return auth()->user()->unreadDirectCounts();
    }
}; ?>

@php
    $user = auth()->user();
    $liturgy = $user->canAccessLiturgy();
    $liturgyAdmin = $user->canManageLiturgy();
    $pastoral = $user->canAccessPastoralCare();
    $firstName = $user->person?->first_name ?? Str::before($user->name, ' ');
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<section class="mx-auto w-full">
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ $greeting }}, {{ $firstName }}</flux:heading>
        <flux:subheading>{{ now()->format('l, F j, Y') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3 items-start">
        {{-- Quick Actions --}}
        <flux:card class="md:col-span-3 !py-3.5 !px-4.5">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon.sparkles variant="micro" class="text-zinc-400" />
                    <span class="text-[13px] font-bold text-zinc-500 dark:text-zinc-400">Quick actions</span>
                </span>

                @if ($liturgy)
                    <flux:button :href="route('services.create')" wire:navigate size="sm" variant="outline">
                        <flux:icon.calendar variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        New Service
                    </flux:button>
                @endif

                @if ($liturgyAdmin)
                    <flux:button :href="route('songs.create')" wire:navigate size="sm" variant="outline">
                        <flux:icon.musical-note variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        Add Song
                    </flux:button>
                @endif

                @if ($pastoral)
                    <flux:button :href="route('pastoral-care.index')" wire:navigate size="sm" variant="outline">
                        <flux:icon.hand-platter variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        Add Prayer Request
                    </flux:button>

                    <flux:button :href="route('pastoral-care.index')" wire:navigate size="sm" variant="outline">
                        <flux:icon.pencil-square variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        Add Pastoral Note
                    </flux:button>
                @endif

                <flux:button :href="route('messages.index', ['compose' => 1])" wire:navigate size="sm" variant="outline">
                    <flux:icon.chat-bubble-left-right variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                    New Conversation
                </flux:button>
            </div>
        </flux:card>

        @if ($liturgy)
            @island(name: 'my-assignments', defer: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="My Assignments" icon="clipboard" :rows="4" />
                @endplaceholder

                <x-dashboard.widget
                    title="My Assignments"
                    icon="clipboard"
                    :href="route('services.index')"
                    link-label="All"
                    :is-empty="$this->myAssignments->isEmpty()"
                    empty-message="You have no upcoming assignments."
                >
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($this->myAssignments as $element)
                            <a href="{{ route('services.show', $element->liturgy) }}" wire:navigate class="flex items-center gap-2.5 py-2.5">
                                <flux:icon name="{{ $element->type->icon() }}" variant="micro" class="shrink-0 text-zinc-400" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">{{ $element->getDisplayTitle() }}</p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $element->liturgy->title ?: ($element->liturgy->template->name ?? 'Untitled Service') }} · {{ $element->liturgy->date->format('D, M j') }}
                                    </p>
                                </div>
                                @if ($element->requiresContent() && ! $element->hasContent())
                                    <flux:badge color="amber" size="sm">Needs content</flux:badge>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </x-dashboard.widget>
            @endisland
        @endif

        @island(name: 'upcoming-services', defer: true)
            @placeholder
                <x-dashboard.widget-skeleton title="Upcoming Services" icon="calendar" :rows="3" />
            @endplaceholder

            <x-dashboard.widget title="Upcoming Services" icon="calendar" :href="route('services.index')" link-label="All">
                @if ($this->upcomingServices->isEmpty())
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No upcoming services.</p>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($this->upcomingServices as $service)
                            <div class="flex items-center gap-3 py-2.5">
                                <div class="flex w-[42px] shrink-0 flex-col items-center">
                                    <span class="text-[10px] font-bold uppercase text-zinc-400">{{ $service->date->format('M') }}</span>
                                    <span class="text-[19px] font-bold leading-tight text-zinc-900 dark:text-zinc-100">{{ $service->date->format('j') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('services.show', $service) }}" wire:navigate class="block truncate text-[13px] font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                                        {{ $service->title ?: ($service->template->name ?? 'Untitled Service') }}
                                    </a>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $service->template->name ?? '' }}</p>
                                </div>
                                @if ($loop->first)
                                    <flux:badge color="emerald" size="sm">{{ $this->relativeDateBadge($service->date) }}</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-dashboard.widget>
        @endisland

        @island(name: 'group-messages', defer: true)
            @placeholder
                <x-dashboard.widget-skeleton title="Group Messages" icon="chat-bubble-left-right" :rows="3" />
            @endplaceholder

            <x-dashboard.widget title="Group Messages" icon="chat-bubble-left-right" :href="route('groups.index')" link-label="Groups">
                <div wire:poll.60s>
                    @if ($this->messageGroups->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">You're not in any groups yet.</p>
                    @else
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($this->messageGroups as $group)
                                @php($unread = $this->groupUnreadCounts[$group->id] ?? 0)
                                @php($latestConversation = $group->latestConversation)
                                @php($latestComment = $latestConversation?->lastComment->first())
                                <a href="{{ route('groups.show', $group) }}" wire:navigate class="flex items-center gap-2.5 py-2.5">
                                    <flux:avatar :name="$group->name" :src="$group->cover_url" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p class="truncate text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">{{ $group->name }}</p>
                                            @if ($unread > 0)
                                                <flux:badge color="emerald" variant="solid" size="sm">{{ $unread }}</flux:badge>
                                            @endif
                                        </div>
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($latestConversation)
                                                {{ $latestConversation->title ?? 'Thread' }} · {{ $latestConversation->last_comment_at?->diffForHumans() }}
                                            @else
                                                No conversations yet
                                            @endif
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-dashboard.widget>
        @endisland

        @island(name: 'direct-messages', defer: true)
            @placeholder
                <x-dashboard.widget-skeleton title="Messages" icon="envelope" :rows="4" />
            @endplaceholder

            <x-dashboard.widget
                title="Messages"
                icon="envelope"
                :href="route('messages.index')"
                link-label="All"
                :is-empty="$this->directMessages->isEmpty()"
                empty-message="You're all caught up."
            >
                <div wire:poll.60s class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach ($this->directMessages as $conversation)
                        @php($title = $conversation->displayTitleFor(auth()->user()))
                        @php($unread = $this->directUnreadCounts[$conversation->id] ?? 0)
                        @php($latestComment = $conversation->lastComment->first())
                        <a href="{{ route('messages.show', $conversation) }}" wire:navigate class="flex items-start gap-2.5 py-2.5">
                            <flux:avatar :name="$title" size="xs" color="auto" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</p>
                                    <span class="ms-auto shrink-0 text-[11px] text-zinc-400">{{ $conversation->last_comment_at?->diffForHumans() }}</span>
                                    @if ($unread > 0)
                                        <flux:badge color="emerald" variant="solid" size="sm">{{ $unread }}</flux:badge>
                                    @endif
                                </div>
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ Str::limit(strip_tags($latestComment->text ?? ''), 60) }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-dashboard.widget>
        @endisland
    </div>
</section>
