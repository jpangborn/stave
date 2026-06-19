<?php

use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\PrayerScheduleSettings;
use App\Models\User;
use App\Services\PrayerScheduleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /** @var 'mine'|'all' */
    public string $scope = 'mine';

    public function mount(): void
    {
        abort_unless($this->user()->canAccessPastoralCare(), 403);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /** @return Builder<Person> */
    private function baseQuery(): Builder
    {
        $personId = $this->user()->person_id;

        return Person::query()->when($this->scope === 'mine', fn (Builder $query) => $query
            ->where('pastoral_care_elder_id', $personId)
            ->where('id', '!=', $personId));
    }

    /** @return array<int, int> */
    #[Computed]
    public function currentWeekPersonIds(): array
    {
        $settings = PrayerScheduleSettings::current();
        $service = app(PrayerScheduleService::class);

        return $service->peopleForWeek($settings, $service->currentWeekIndex($settings))
            ->pluck('id')
            ->all();
    }

    public function currentWeekLabel(): string
    {
        $settings = PrayerScheduleSettings::current();
        $service = app(PrayerScheduleService::class);
        $index = $service->currentWeekIndex($settings);

        return 'Week '.($index + 1).' · '.$service->weekRange($settings, $index);
    }

    /** @return array{count: int, openRequests: int, awaitingBaptism: int, inRota: int} */
    #[Computed]
    public function stats(): array
    {
        $ids = $this->baseQuery()->pluck('id');

        return [
            'count' => $ids->count(),
            'openRequests' => PrayerRequest::query()->open()->whereIn('person_id', $ids)->count(),
            'awaitingBaptism' => $this->baseQuery()->where('baptized', false)->count(),
            'inRota' => count(array_intersect($this->currentWeekPersonIds, $ids->all())),
        ];
    }

    /** @return Collection<int, Person> */
    #[Computed]
    public function people(): Collection
    {
        return $this->baseQuery()
            ->with(['household', 'pastoralCareElder'])
            ->withCount(['prayerRequests as open_requests_count' => fn (Builder $query) => $query->whereNull('completed_at')])
            ->withMax('pastoralNotes as last_note_at', 'created_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Pastoral Care</flux:heading>
            <flux:subheading>
                {{ $scope === 'mine'
                    ? $this->stats['count'].' people under your care'
                    : $this->stats['count'].' people across the congregation' }}
            </flux:subheading>
        </div>
        <flux:radio.group variant="segmented" size="sm" wire:model.live="scope" data-test="scope-toggle">
            <flux:radio value="mine">My Assignees</flux:radio>
            <flux:radio value="all">All Congregation</flux:radio>
        </flux:radio.group>
    </div>

    {{-- Stat cards --}}
    <div class="mb-6 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <flux:card class="!p-4">
            <p class="text-xs font-semibold text-zinc-500">{{ $scope === 'mine' ? 'Under your care' : 'In the congregation' }}</p>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight">{{ $this->stats['count'] }}</span>
                <span class="text-xs text-zinc-400">people</span>
            </div>
        </flux:card>
        <flux:card class="!p-4">
            <p class="text-xs font-semibold text-zinc-500">Open prayer requests</p>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">{{ $this->stats['openRequests'] }}</span>
                <span class="text-xs text-zinc-400">active</span>
            </div>
        </flux:card>
        <flux:card class="!p-4">
            <p class="text-xs font-semibold text-zinc-500">Awaiting baptism</p>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-amber-700 dark:text-amber-500">{{ $this->stats['awaitingBaptism'] }}</span>
                <span class="text-xs text-zinc-400">people</span>
            </div>
        </flux:card>
        <flux:card class="!p-4">
            <p class="text-xs font-semibold text-zinc-500">In this week's rota</p>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight">{{ $this->stats['inRota'] }}</span>
                <span class="text-xs text-zinc-400">to pray for</span>
            </div>
        </flux:card>
    </div>

    {{-- Section heading --}}
    <div class="mb-3 flex items-center justify-between gap-3">
        <flux:heading size="lg">{{ $scope === 'mine' ? 'People under your care' : 'All congregation' }}</flux:heading>
        <flux:badge color="emerald" icon="calendar-date-range">This week: {{ $this->currentWeekLabel() }}</flux:badge>
    </div>

    {{-- People grid --}}
    @if ($this->people->isEmpty())
        <flux:card class="py-12 text-center">
            <flux:icon icon="heart" class="mx-auto size-8 text-zinc-300" />
            <flux:heading class="mt-3">{{ $scope === 'mine' ? 'No one is assigned to your care yet' : 'No people yet' }}</flux:heading>
            <flux:subheading>{{ $scope === 'mine' ? 'People you shepherd will appear here.' : 'Add people to your congregation to get started.' }}</flux:subheading>
        </flux:card>
    @else
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach ($this->people as $person)
                <a
                    href="{{ route('people.show', $person) }}"
                    wire:navigate
                    wire:key="care-{{ $person->id }}"
                    class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600"
                    data-test="person-card"
                >
                    <div class="flex items-center gap-3">
                        <x-person-avatar :person="$person" size="lg" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $person->full_name }}</p>
                            <p class="truncate text-xs text-zinc-500">
                                {{ $person->household?->name ? $person->household->name.' household' : 'No household' }}@if ($scope === 'all' && $person->pastoralCareElder) · {{ $person->pastoralCareElder->full_name }}@endif
                            </p>
                        </div>
                        <x-membership-badge :status="$person->membership_status" />
                        @if (in_array($person->id, $this->currentWeekPersonIds, true))
                            <flux:badge size="sm" color="emerald">This week</flux:badge>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 border-t border-zinc-100 pt-2.5 text-xs text-zinc-500 dark:border-zinc-700/60">
                        <span class="inline-flex items-center gap-1.5">
                            @if ($person->baptized && $person->baptism_date)
                                <flux:icon icon="waves" class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                {{ $person->baptism_date->format('M j, Y') }}
                            @else
                                <span class="text-zinc-400">Not yet baptized</span>
                            @endif
                        </span>
                        <span @class(['inline-flex items-center gap-1.5', 'font-semibold text-emerald-700 dark:text-emerald-400' => $person->open_requests_count > 0])>
                            <flux:icon icon="hand-raised" class="size-3.5" />
                            {{ $person->open_requests_count > 0 ? $person->open_requests_count.' '.Str::plural('request', $person->open_requests_count) : 'No open requests' }}
                        </span>
                        <span class="ms-auto text-zinc-400">
                            {{ $person->last_note_at ? 'Last note '.Carbon::parse($person->last_note_at)->format('M j') : 'No notes' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
