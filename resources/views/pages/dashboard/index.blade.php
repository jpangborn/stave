<?php

use App\Enums\PrayerRequestVisibility;
use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\PrayerScheduleSettings;
use App\Models\Service;
use App\Models\Song;
use App\Services\PrayerScheduleService;
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

    /**
     * Readiness snapshot for the current (soonest upcoming) service, or
     * null when there is no upcoming service.
     *
     * @return array{service: Service, missing: int, unassigned: int, total: int, ready: int, pct: int}|null
     */
    #[Computed]
    public function readiness(): ?array
    {
        if (! auth()->user()->canManageLiturgy()) {
            return null;
        }

        $service = Service::current();

        if (! $service) {
            return null;
        }

        $missing = $service->missingContentCount();
        $unassigned = $service->unassignedCount();
        $total = $service->elementCount();
        $ready = max(0, $total - $missing - $unassigned);

        return [
            'service' => $service,
            'missing' => $missing,
            'unassigned' => $unassigned,
            'total' => $total,
            'ready' => $ready,
            'pct' => $total > 0 ? (int) round($ready / $total * 100) : 100,
        ];
    }

    /** @return Collection<int, Song> */
    #[Computed]
    public function rotationCandidates(): Collection
    {
        if (! auth()->user()->canManageLiturgy()) {
            return collect();
        }

        return Song::query()->withLastUsedDate()->orderBy('last_used_date')->orderBy('name')->limit(5)->get();
    }

    /**
     * The current prayer-schedule week, matching the Prayer Schedule page's
     * display semantics, or null when nobody is eligible for the schedule.
     *
     * @return array{label: string, range: string, people: Collection<int, Person>}|null
     */
    #[Computed]
    public function prayerWeek(): ?array
    {
        if (! auth()->user()->canAccessPastoralCare()) {
            return null;
        }

        $service = app(PrayerScheduleService::class);
        $settings = PrayerScheduleSettings::current();

        if ($service->stats($settings)['total'] === 0) {
            return null;
        }

        $weekIndex = $service->currentWeekIndex($settings);

        return [
            'label' => 'Week '.($weekIndex + 1),
            'range' => $service->weekRange($settings, $weekIndex),
            'people' => $service->peopleForWeek($settings, $weekIndex),
        ];
    }

    /** @return Collection<int, PrayerRequest> */
    #[Computed]
    public function recentPrayerRequests(): Collection
    {
        if (! auth()->user()->canAccessPastoralCare()) {
            return collect();
        }

        return PrayerRequest::query()->open()->with('person')->latest()->limit(5)->get();
    }

    /** @return Collection<int, PastoralNote> */
    #[Computed]
    public function recentPastoralNotes(): Collection
    {
        if (! auth()->user()->canAccessPastoralCare()) {
            return collect();
        }

        return PastoralNote::query()->with('person', 'author')->latest()->limit(5)->get();
    }

    /** @return Collection<int, Person> */
    #[Computed]
    public function careList(): Collection
    {
        if (! auth()->user()->canAccessPastoralCare()) {
            return collect();
        }

        $me = auth()->user()->person;

        if ($me === null) {
            return collect();
        }

        return $me->assignedCongregants()
            ->withLastPastoralNoteDate()
            ->orderBy('last_noted_at')
            ->orderBy('last_name')
            ->limit(5)
            ->get();
    }

    /** @return Collection<int, Person> */
    #[Computed]
    public function upcomingBirthdays(): Collection
    {
        if (! auth()->user()->canAccessPastoralCare()) {
            return collect();
        }

        return Person::query()
            ->birthdayWithin(30)
            ->get()
            ->sortBy(fn (Person $p) => $this->nextBirthday($p))
            ->take(5)
            ->values();
    }

    public function nextBirthday(Person $person): Carbon
    {
        $next = $person->birth_date->copy()->year(today()->year);

        return $next->lt(today()) ? $next->addYear() : $next;
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
                                        {{ $element->liturgy->display_title }} · {{ $element->liturgy->date->format('D, M j') }}
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
                                        {{ $service->display_title }}
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

        @if ($liturgyAdmin)
            @island(name: 'service-readiness', defer: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="Service Readiness" icon="circle-check-big" chip="emerald" :rows="3" />
                @endplaceholder

                @php($readiness = $this->readiness)
                <x-dashboard.widget
                    title="Service Readiness"
                    icon="circle-check-big"
                    chip="emerald"
                    :href="$readiness ? route('services.show', $readiness['service']) : null"
                    link-label="Open"
                >
                    @if (! $readiness)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No upcoming service.</p>
                    @else
                        <div class="flex items-center justify-between">
                            <p class="text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">{{ $readiness['service']->display_title }}</p>
                            <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-500">{{ $this->relativeDateBadge($readiness['service']->date) }}</span>
                        </div>

                        <div class="mt-2.5">
                            <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-2 rounded-full bg-emerald-500 transition-[width] duration-500" style="width: {{ $readiness['pct'] }}%"></div>
                            </div>
                            <div class="mt-1 flex items-center justify-between">
                                <p class="text-xs text-zinc-400">{{ $readiness['ready'] }} of {{ $readiness['total'] }} elements ready</p>
                                <span class="text-xs font-bold text-zinc-500">{{ $readiness['pct'] }}%</span>
                            </div>
                        </div>

                        <div class="mt-2.5">
                            @if ($readiness['missing'] === 0 && $readiness['unassigned'] === 0)
                                <div class="flex items-center gap-2 rounded-lg bg-emerald-50 p-2.5 dark:bg-emerald-950">
                                    <flux:icon.check-circle variant="micro" class="shrink-0 text-emerald-700 dark:text-emerald-400" />
                                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Ready for {{ $readiness['service']->date->format('l') }}</p>
                                </div>
                            @else
                                <div class="flex gap-2">
                                    <div class="flex-1 rounded-lg bg-amber-50 px-3 py-2 dark:bg-amber-950">
                                        <flux:icon.exclamation-triangle variant="micro" class="text-amber-700 dark:text-amber-400" />
                                        <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $readiness['missing'] }}</p>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">missing content</p>
                                    </div>
                                    <div class="flex-1 rounded-lg bg-zinc-100 px-3 py-2 dark:bg-zinc-800">
                                        <flux:icon.users variant="micro" class="text-zinc-500 dark:text-zinc-400" />
                                        <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $readiness['unassigned'] }}</p>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">unassigned</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </x-dashboard.widget>
            @endisland

            @island(name: 'rotation-candidates', lazy: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="Rotation Candidates" icon="library-big" :rows="4" />
                @endplaceholder

                <x-dashboard.widget
                    title="Rotation Candidates"
                    icon="library-big"
                    :href="route('songs.index')"
                    link-label="Songs"
                >
                    @if ($this->rotationCandidates->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No songs in the library yet.</p>
                    @else
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($this->rotationCandidates as $song)
                                <div class="flex items-center justify-between gap-2.5 py-2.5">
                                    <a href="{{ route('songs.show', $song) }}" wire:navigate class="truncate text-[13px] text-emerald-600 underline underline-offset-2 hover:text-emerald-700 dark:text-emerald-400">
                                        {{ $song->name }}
                                    </a>
                                    <span class="whitespace-nowrap text-xs text-zinc-500">{{ $song->last_used_date?->format('M j, Y') ?? 'Never' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-dashboard.widget>
            @endisland
        @endif

        @if ($pastoral)
            @island(name: 'prayer-this-week', defer: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="Prayer This Week" icon="hand-platter" :rows="4" />
                @endplaceholder

                @php($prayerWeek = $this->prayerWeek)
                <x-dashboard.widget
                    title="Prayer This Week"
                    icon="hand-platter"
                    :href="route('prayer-schedule.index')"
                    link-label="Schedule"
                >
                    @if (! $prayerWeek)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No prayer schedule configured.</p>
                    @else
                        <div class="mb-2 flex items-center gap-2">
                            <flux:badge color="emerald" size="sm">{{ $prayerWeek['label'] }}</flux:badge>
                            <span class="text-xs text-zinc-500">{{ $prayerWeek['range'] }}</span>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($prayerWeek['people'] as $person)
                                <div class="flex items-center gap-2.5 py-2.5">
                                    <flux:icon.hand-platter variant="micro" class="text-zinc-400" />
                                    <span class="text-[13px] font-medium">{{ $person->full_name }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-dashboard.widget>
            @endisland

            @island(name: 'recent-prayer-requests', lazy: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="Prayer Requests" icon="heart" :rows="4" />
                @endplaceholder

                <x-dashboard.widget
                    title="Prayer Requests"
                    icon="heart"
                    :href="route('pastoral-care.index')"
                    link-label="All"
                    :is-empty="$this->recentPrayerRequests->isEmpty()"
                    empty-message="No open prayer requests."
                >
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($this->recentPrayerRequests as $r)
                            <div class="py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('people.show', $r->person) }}" wire:navigate class="flex-1 truncate text-[13px] font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                                        {{ $r->person->full_name }}
                                    </a>
                                    <flux:badge size="sm" :color="match ($r->visibility) {
                                        PrayerRequestVisibility::BULLETIN => 'purple',
                                        PrayerRequestVisibility::PRIVATE => 'amber',
                                    }">{{ $r->visibility->label() }}</flux:badge>
                                    @if ($r->created_at->lte(now()->subWeeks(3)))
                                        <flux:badge color="amber" size="sm">{{ (int) floor($r->created_at->diffInWeeks(now())) }}w open</flux:badge>
                                    @endif
                                    <span class="shrink-0 text-[11px] text-zinc-400">{{ $r->created_at->diffForHumans(short: true) }}</span>
                                </div>
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ Str::limit($r->body, 70) }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-dashboard.widget>
            @endisland

            @island(name: 'recent-pastoral-notes', lazy: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="Pastoral Notes" icon="pencil-square" :rows="4" />
                @endplaceholder

                <x-dashboard.widget
                    title="Pastoral Notes"
                    icon="pencil-square"
                    :href="route('pastoral-care.index')"
                    link-label="All"
                >
                    @if ($this->recentPastoralNotes->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No pastoral notes yet.</p>
                    @else
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($this->recentPastoralNotes as $note)
                                <div class="flex items-start gap-2.5 py-2.5">
                                    <x-person-avatar :person="$note->person" size="xs" />
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('people.show', $note->person) }}" wire:navigate class="block truncate text-[13px] font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                                            {{ $note->person->full_name }}
                                        </a>
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ Str::limit($note->body, 70) }}</p>
                                        <p class="text-[11px] text-zinc-400">{{ $note->author->name }} · {{ $note->created_at->diffForHumans(short: true) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-dashboard.widget>
            @endisland

            @island(name: 'my-care-list', lazy: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="My Care List" icon="clock" :rows="4" />
                @endplaceholder

                <x-dashboard.widget
                    title="My Care List"
                    icon="clock"
                    :href="route('pastoral-care.index')"
                    link-label="All"
                >
                    @if ($this->careList->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No one is assigned to your care.</p>
                    @else
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($this->careList as $p)
                                <div class="flex items-center gap-2.5 py-2.5">
                                    <flux:icon.clock variant="micro" class="text-zinc-400" />
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('people.show', $p) }}" wire:navigate class="block truncate text-[13px] font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                                            {{ $p->full_name }}
                                        </a>
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">Last contact {{ $p->last_noted_at?->diffForHumans() ?? 'never' }}</p>
                                    </div>
                                    @if ($p->last_noted_at === null || $p->last_noted_at->lte(now()->subWeeks(8)))
                                        <flux:badge color="amber" size="sm">Overdue</flux:badge>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-dashboard.widget>
            @endisland

            @island(name: 'upcoming-birthdays', lazy: true)
                @placeholder
                    <x-dashboard.widget-skeleton title="Upcoming Birthdays" icon="cake" :rows="4" />
                @endplaceholder

                <x-dashboard.widget
                    title="Upcoming Birthdays"
                    icon="cake"
                    :href="route('people.index')"
                    link-label="People"
                >
                    @if ($this->upcomingBirthdays->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No birthdays in the next 30 days.</p>
                    @else
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($this->upcomingBirthdays as $p)
                                @php($next = $this->nextBirthday($p))
                                <div class="flex items-center gap-2.5 py-2.5">
                                    <x-person-avatar :person="$p" size="xs" />
                                    <a href="{{ route('people.show', $p) }}" wire:navigate class="flex-1 truncate text-[13px] font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                                        {{ $p->full_name }}
                                    </a>
                                    <div class="shrink-0 text-right">
                                        <p class="text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">{{ $next->format('M j') }}</p>
                                        <p class="text-[11px] text-zinc-400">{{ $next->isToday() ? 'Today' : 'in '.(int) today()->diffInDays($next).' days' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-dashboard.widget>
            @endisland
        @endif
    </div>
</section>
