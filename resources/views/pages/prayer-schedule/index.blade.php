<?php

use App\Enums\MembershipStatus;
use App\Mail\PrayerScheduleDigestMail;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\PrayerScheduleSettings;
use App\Models\User;
use App\Services\PrayerScheduleService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /** @var 'weeks'|'roster' */
    public string $view = 'weeks';

    /** @var array<int, string> */
    public array $includeStatuses = [];

    public int $cycleWeeks = 8;

    public string $groupBy = 'alpha';

    public ?string $previewHtml = null;

    public int $previewWeek = 0;

    public function mount(): void
    {
        abort_unless($this->user()->canAccessPastoralCare(), 403);

        $settings = PrayerScheduleSettings::current();
        $this->includeStatuses = $settings->include_statuses;
        $this->cycleWeeks = $settings->cycle_weeks;
        $this->groupBy = $settings->group_by->value;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function service(): PrayerScheduleService
    {
        return app(PrayerScheduleService::class);
    }

    private function persist(): void
    {
        PrayerScheduleSettings::current()->update([
            'cycle_weeks' => $this->cycleWeeks,
            'group_by' => $this->groupBy,
            'include_statuses' => array_values($this->includeStatuses),
        ]);

        unset($this->settings, $this->buckets, $this->stats, $this->roster, $this->peopleWithOpenBulletinRequests);
    }

    public function toggleStatus(string $status): void
    {
        if (in_array($status, $this->includeStatuses, true)) {
            $this->includeStatuses = array_values(array_diff($this->includeStatuses, [$status]));
        } else {
            $this->includeStatuses[] = $status;
        }

        $this->persist();
    }

    public function incrementWeeks(): void
    {
        $this->cycleWeeks = min(13, $this->cycleWeeks + 1);
        $this->persist();
    }

    public function decrementWeeks(): void
    {
        $this->cycleWeeks = max(4, $this->cycleWeeks - 1);
        $this->persist();
    }

    public function updatedGroupBy(): void
    {
        $this->persist();
    }

    public function previewEmail(?int $week = null): void
    {
        $week ??= $this->currentWeekIndex();
        $this->previewWeek = $week;

        $settings = $this->settings;
        $people = $this->service()->bulletinDigestForWeek($settings, $week, $this->includeStatuses);

        $this->previewHtml = (new PrayerScheduleDigestMail(
            recipient: $this->user(),
            people: $people,
            weekNumber: $week + 1,
            totalWeeks: $settings->cycle_weeks,
            weekRange: $this->service()->weekRange($settings, $week),
        ))->render();

        Flux::modal('email-preview')->show();
    }

    #[Computed]
    public function settings(): PrayerScheduleSettings
    {
        return PrayerScheduleSettings::current();
    }

    /** @return Collection<int, Collection<int, Person>> */
    #[Computed]
    public function buckets(): Collection
    {
        return $this->service()->buckets($this->settings, $this->includeStatuses);
    }

    public function currentWeekIndex(): int
    {
        return $this->service()->currentWeekIndex($this->settings);
    }

    /** @return array{total: int, weeks: int, perWeek: int, currentWeekIndex: int} */
    #[Computed]
    public function stats(): array
    {
        return $this->service()->stats($this->settings, $this->includeStatuses);
    }

    /** @return Collection<int, array{person: Person, week: int}> */
    #[Computed]
    public function roster(): Collection
    {
        return $this->buckets
            ->flatMap(fn (Collection $people, int $week): Collection => $people->map(fn ($person): array => [
                'person' => $person,
                'week' => $week,
            ]))
            ->sortBy([
                ['person.last_name', 'asc'],
                ['person.first_name', 'asc'],
            ])
            ->values();
    }

    /** @return array<int, int> */
    #[Computed]
    public function peopleWithOpenBulletinRequests(): array
    {
        return PrayerRequest::query()->open()->bulletin()->distinct()->pluck('person_id')->all();
    }

    public function statLine(): string
    {
        $stats = $this->stats;
        $range = $this->service()->weekRange($this->settings, $stats['currentWeekIndex']);

        return $stats['total'].' people across '.$stats['weeks'].' weeks · about '.$stats['perWeek']
            .' per week · This week: Week '.($stats['currentWeekIndex'] + 1).' ('.$range.')';
    }

    public function dotClass(MembershipStatus $status): string
    {
        return match ($status->color()) {
            'emerald' => 'bg-emerald-500',
            'amber' => 'bg-amber-500',
            'sky' => 'bg-sky-500',
            default => 'bg-zinc-400',
        };
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="mb-4">
        <flux:heading size="xl" level="1">Prayer Schedule</flux:heading>
        <flux:subheading>Pray through the congregation over a {{ $cycleWeeks }}-week cycle.</flux:subheading>
    </div>

    {{-- Controls --}}
    <flux:card class="mb-3 flex flex-wrap items-start justify-between gap-4 !p-4">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold text-zinc-500">Cycle length</span>
                <div class="inline-flex items-center overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <flux:button size="xs" variant="subtle" icon="minus" wire:click="decrementWeeks" aria-label="Fewer weeks" />
                    <span class="min-w-16 text-center text-sm font-semibold">{{ $cycleWeeks }} weeks</span>
                    <flux:button size="xs" variant="subtle" icon="plus" wire:click="incrementWeeks" aria-label="More weeks" />
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold text-zinc-500">Include</span>
                <div class="flex flex-wrap gap-1.5" data-test="status-chips">
                    @foreach ([MembershipStatus::MEMBER, MembershipStatus::CATECHUMEN, MembershipStatus::ADHERENT, MembershipStatus::VISITOR] as $status)
                        @php($on = in_array($status->value, $includeStatuses, true))
                        <button
                            type="button"
                            wire:click="toggleStatus('{{ $status->value }}')"
                            @class([
                                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition',
                                'border-emerald-600 bg-emerald-50 text-emerald-700 dark:border-emerald-500 dark:bg-emerald-950/50 dark:text-emerald-300' => $on,
                                'border-zinc-200 text-zinc-500 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' => ! $on,
                            ])
                            data-test="status-chip-{{ $status->value }}"
                            @if ($on) data-on="true" @endif
                        >
                            @if ($on)
                                <flux:icon icon="check" variant="micro" class="size-3.5" />
                            @endif
                            {{ $status->label() }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold text-zinc-500">Group by</span>
                <flux:radio.group variant="segmented" size="sm" wire:model.live="groupBy">
                    <flux:radio value="alpha">Alphabetical</flux:radio>
                    <flux:radio value="household">By household</flux:radio>
                </flux:radio.group>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold text-zinc-500">View</span>
                <flux:radio.group variant="segmented" size="sm" wire:model.live="view">
                    <flux:radio value="weeks">Weeks</flux:radio>
                    <flux:radio value="roster">Roster</flux:radio>
                </flux:radio.group>
            </div>
        </div>

        <div>
            <flux:button size="sm" variant="primary" icon="envelope" wire:click="previewEmail" data-test="preview-email">
                Preview Monday email
            </flux:button>
        </div>
    </flux:card>

    <p class="mb-5 text-sm text-zinc-500" data-test="stat-line">{{ $this->statLine() }}</p>

    @if ($view === 'weeks')
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3" data-test="weeks-view">
            @foreach ($this->buckets as $weekIndex => $week)
                @php($isCurrent = $weekIndex === $this->currentWeekIndex())
                <div @class([
                    'overflow-hidden rounded-xl border bg-white dark:bg-zinc-800',
                    'border-emerald-400 dark:border-emerald-500' => $isCurrent,
                    'border-zinc-200 dark:border-zinc-700' => ! $isCurrent,
                ]) wire:key="week-{{ $weekIndex }}">
                    <div @class([
                        'flex items-center gap-2 border-b px-4 py-3',
                        'border-zinc-100 bg-emerald-50 dark:border-zinc-700 dark:bg-emerald-950/40' => $isCurrent,
                        'border-zinc-100 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/60' => ! $isCurrent,
                    ])>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold">Week {{ $weekIndex + 1 }}</span>
                                @if ($isCurrent)
                                    <flux:badge size="sm" color="emerald">This week</flux:badge>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-zinc-500">{{ $this->service()->weekRange($this->settings, $weekIndex) }} · {{ $week->count() }} people</p>
                        </div>
                        <flux:button size="xs" variant="ghost" icon="envelope" wire:click="previewEmail({{ $weekIndex }})" aria-label="Preview email for this week" />
                    </div>
                    <div class="px-4 py-2">
                        @forelse ($week as $person)
                            <div class="flex items-center gap-2.5 border-b border-zinc-100 py-1.5 last:border-0 dark:border-zinc-700/60" wire:key="week-{{ $weekIndex }}-person-{{ $person->id }}">
                                <x-person-avatar :person="$person" size="xs" />
                                <span class="flex-1 truncate text-[13px] font-medium">{{ $person->full_name }}</span>
                                <span class="size-1.5 rounded-full {{ $this->dotClass($person->membership_status) }}"></span>
                                @if (in_array($person->id, $this->peopleWithOpenBulletinRequests, true))
                                    <flux:icon icon="hand-raised" class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                @endif
                            </div>
                        @empty
                            <p class="py-3 text-center text-xs text-zinc-400">No one this week</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700" data-test="roster-view">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Household</flux:table.column>
                    <flux:table.column>Prayer week</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->roster as $row)
                        @php($person = $row['person'])
                        @php($isCurrent = $row['week'] === $this->currentWeekIndex())
                        <flux:table.row wire:key="roster-{{ $person->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-2.5">
                                    <x-person-avatar :person="$person" size="xs" />
                                    <span class="font-medium">{{ $person->full_name }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <x-membership-badge :status="$person->membership_status" />
                            </flux:table.cell>
                            <flux:table.cell class="text-zinc-500">{{ $person->household?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span @class(['font-semibold', 'text-emerald-700 dark:text-emerald-400' => $isCurrent])>Week {{ $row['week'] + 1 }}</span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    <flux:modal name="email-preview" class="w-full max-w-2xl">
        <div class="space-y-3">
            <flux:heading size="lg">Monday email · Week {{ $previewWeek + 1 }}</flux:heading>
            @if ($previewHtml !== null)
                <iframe srcdoc="{{ $previewHtml }}" class="h-[60vh] w-full rounded-lg border border-zinc-200 dark:border-zinc-700" title="Monday email preview"></iframe>
            @endif
            <div class="flex items-center justify-between gap-3">
                <flux:text size="sm" class="text-zinc-500">Sends automatically every Monday at 6:00 AM to all elders.</flux:text>
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
