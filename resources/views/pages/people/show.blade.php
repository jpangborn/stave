<?php

use App\Enums\PrayerRequestVisibility;
use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\PrayerScheduleSettings;
use App\Models\User;
use App\Services\PrayerScheduleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Person $person;

    public bool $showCompleted = false;

    public string $newRequestBody = '';

    public string $newRequestVisibility = 'bulletin';

    public string $newNote = '';

    public function mount(Person $person): void
    {
        $this->person = $person->load(['user', 'household', 'pastoralCareElder', 'offices']);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    #[Computed]
    public function canAccessPastoralCare(): bool
    {
        return $this->user()->canAccessPastoralCare();
    }

    /* ----------------------- prayer requests ----------------------- */

    /** @return Collection<int, PrayerRequest> */
    #[Computed]
    public function prayerRequests(): Collection
    {
        $all = $this->person->prayerRequests()->orderByDesc('created_at')->get();

        $open = $all->whereNull('completed_at')->values();
        $completed = $all->whereNotNull('completed_at');

        if (! $this->showCompleted) {
            $cutoff = now()->subDays(7);
            $completed = $completed->filter(fn (PrayerRequest $request): bool => $request->completed_at->greaterThanOrEqualTo($cutoff));
        }

        return $open->concat($completed->sortByDesc('completed_at')->values());
    }

    #[Computed]
    public function openRequestCount(): int
    {
        return $this->person->prayerRequests()->open()->count();
    }

    #[Computed]
    public function hiddenCompletedCount(): int
    {
        if ($this->showCompleted) {
            return 0;
        }

        return $this->person->prayerRequests()
            ->completed()
            ->where('completed_at', '<', now()->subDays(7))
            ->count();
    }

    public function addPrayerRequest(): void
    {
        $body = trim($this->newRequestBody);

        if ($body === '') {
            $this->addError('newRequestBody', 'Enter a prayer request.');

            return;
        }

        $this->person->prayerRequests()->create([
            'body' => $body,
            'visibility' => PrayerRequestVisibility::tryFrom($this->newRequestVisibility) ?? PrayerRequestVisibility::BULLETIN,
            'created_by_user_id' => $this->user()->id,
        ]);

        $this->newRequestBody = '';
        $this->newRequestVisibility = 'bulletin';
        unset($this->prayerRequests, $this->openRequestCount, $this->hiddenCompletedCount);
    }

    public function toggleComplete(int $requestId): void
    {
        $request = $this->person->prayerRequests()->whereKey($requestId)->first();

        if ($request === null) {
            return;
        }

        $request->update(['completed_at' => $request->completed_at ? null : now()]);
        unset($this->prayerRequests, $this->openRequestCount, $this->hiddenCompletedCount);
    }

    /* -------------------------- pastoral notes ---------------------- */

    /** @return Collection<int, PastoralNote> */
    #[Computed]
    public function notes(): Collection
    {
        if (! $this->canAccessPastoralCare) {
            return collect();
        }

        return $this->person->pastoralNotes()->with('author')->latest()->get();
    }

    public function addNote(): void
    {
        abort_unless($this->canAccessPastoralCare, 403);

        $body = trim($this->newNote);

        if ($body === '') {
            $this->addError('newNote', 'Write a note.');

            return;
        }

        $this->person->pastoralNotes()->create([
            'author_id' => $this->user()->id,
            'body' => $body,
        ]);

        $this->newNote = '';
        unset($this->notes);
    }

    /* ----------------------------- rota ----------------------------- */

    /** @return array{label?: string, excluded?: string} */
    #[Computed]
    public function rota(): array
    {
        $settings = PrayerScheduleSettings::current();

        if (! in_array($this->person->membership_status->value, $settings->include_statuses, true)) {
            return ['excluded' => Str::plural($this->person->membership_status->label()).' are not in this rotation'];
        }

        $service = app(PrayerScheduleService::class);

        foreach ($service->buckets($settings) as $index => $week) {
            if ($week->contains('id', $this->person->id)) {
                return ['label' => 'Week '.($index + 1).' · '.$service->weekRange($settings, $index)];
            }
        }

        return ['excluded' => 'Not currently scheduled'];
    }

    #[On('person-saved')]
    public function refreshPerson(): void
    {
        $this->person->refresh()->load(['user', 'household', 'pastoralCareElder', 'offices']);
        unset($this->prayerRequests, $this->openRequestCount, $this->hiddenCompletedCount, $this->notes, $this->rota);
    }

    #[On('person-deleted')]
    public function onPersonDeleted(): void
    {
        $this->redirectRoute('people.index', navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <flux:button :href="route('people.index')" wire:navigate variant="ghost" size="sm" icon="arrow-left" class="mb-4">
        People
    </flux:button>

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-person-avatar :person="$person" size="xl" />
            <div>
                <flux:heading size="xl" level="1">{{ $person->full_name }}</flux:heading>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                    <x-membership-badge :status="$person->membership_status" :reason="$person->termination_reason" />
                    @foreach ($person->offices as $office)
                        <x-office-chip :kind="$office->kind" />
                    @endforeach
                    @if ($person->household)
                        <flux:text size="sm" class="text-zinc-500">{{ $person->household->name }} household</flux:text>
                    @endif
                    @if ($person->pastoralCareElder)
                        <span class="text-zinc-300 dark:text-zinc-600">·</span>
                        <flux:text size="sm" class="text-zinc-500">Shepherded by {{ $person->pastoralCareElder->full_name }}</flux:text>
                    @endif
                </div>
            </div>
        </div>
        <flux:button variant="outline" size="sm" icon="pencil-square" wire:click="$dispatch('open-person-drawer', { personId: {{ $person->id }} })" data-test="edit-profile">
            Edit profile
        </flux:button>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_340px]">
        {{-- LEFT --}}
        <div class="space-y-6">
            {{-- Prayer Requests --}}
            <flux:card class="overflow-hidden !p-0" data-test="prayer-requests">
                <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <flux:icon icon="hand-raised" class="size-5 text-emerald-600 dark:text-emerald-400" />
                    <flux:heading size="lg">Prayer Requests</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $this->openRequestCount }} open</flux:badge>
                    <flux:spacer />
                    <flux:field variant="inline">
                        <flux:label class="!text-xs text-zinc-500">Show completed</flux:label>
                        <flux:switch wire:model.live="showCompleted" data-test="show-completed" />
                    </flux:field>
                </div>

                <div>
                    @forelse ($this->prayerRequests as $request)
                        @php($done = $request->completed_at !== null)
                        <div class="flex items-start gap-3 border-b border-zinc-100 px-5 py-3.5 dark:border-zinc-700/60" wire:key="req-{{ $request->id }}" data-test="prayer-request">
                            <button
                                type="button"
                                wire:click="toggleComplete({{ $request->id }})"
                                @class([
                                    'mt-0.5 grid size-5 shrink-0 place-items-center rounded-md border transition',
                                    'border-transparent bg-accent text-white' => $done,
                                    'border-zinc-300 hover:border-zinc-400 dark:border-zinc-600' => ! $done,
                                ])
                                aria-label="{{ $done ? 'Mark as open' : 'Mark as completed' }}"
                            >
                                @if ($done)
                                    <flux:icon icon="check" variant="micro" class="size-3.5" />
                                @endif
                            </button>
                            <div class="min-w-0 flex-1">
                                <p @class(['text-sm leading-relaxed', 'text-zinc-400 line-through' => $done])>{{ $request->body }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="text-xs text-zinc-500">
                                        {{ $done ? 'Completed '.$request->completed_at->format('M j') : 'Added '.$request->created_at->format('M j') }}
                                    </span>
                                    <flux:badge size="sm" :color="$request->visibility->color()" :icon="$request->visibility->icon()">{{ $request->visibility->label() }}</flux:badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-sm text-zinc-500">No prayer requests yet.</div>
                    @endforelse

                    @if ($this->hiddenCompletedCount > 0)
                        <div class="border-b border-zinc-100 px-5 py-2.5 text-xs text-zinc-500 dark:border-zinc-700/60" data-test="hidden-completed">
                            {{ $this->hiddenCompletedCount }} completed {{ Str::plural('request', $this->hiddenCompletedCount) }} hidden — turn on “Show completed” to view
                        </div>
                    @endif

                    <div class="flex items-center gap-2 bg-zinc-50 px-5 py-3.5 dark:bg-zinc-800/50">
                        <flux:input wire:model="newRequestBody" placeholder="Add a prayer request…" size="sm" class="flex-1" wire:keydown.enter.prevent="addPrayerRequest" />
                        <flux:radio.group variant="segmented" size="sm" wire:model="newRequestVisibility">
                            <flux:radio value="bulletin">Bulletin</flux:radio>
                            <flux:radio value="private">Private</flux:radio>
                        </flux:radio.group>
                        <flux:button variant="primary" size="sm" wire:click="addPrayerRequest">Add</flux:button>
                    </div>
                </div>
            </flux:card>

            {{-- Pastoral Notes --}}
            @if ($this->canAccessPastoralCare)
                <flux:card class="overflow-hidden !p-0" data-test="pastoral-notes">
                    <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                        <flux:icon icon="lock-closed" class="size-5 text-amber-600 dark:text-amber-400" />
                        <flux:heading size="lg">Pastoral Notes</flux:heading>
                        <flux:badge size="sm" color="amber" icon="lock-closed">Visible to elders only</flux:badge>
                    </div>

                    <div class="divide-y divide-zinc-100 px-5 dark:divide-zinc-700/60">
                        @forelse ($this->notes as $note)
                            <div class="py-3.5" wire:key="note-{{ $note->id }}">
                                <div class="mb-1.5 flex items-center gap-2">
                                    @if ($note->author)
                                        <flux:avatar size="xs" :name="$note->author->name" :src="$note->author->gravatar" color="auto" />
                                    @else
                                        <flux:avatar size="xs" name="—" color="auto" />
                                    @endif
                                    <span class="text-xs font-semibold">{{ $note->author?->name ?? 'Former user' }}</span>
                                    <span class="text-xs text-zinc-400">{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm leading-relaxed">{{ $note->body }}</p>
                            </div>
                        @empty
                            <div class="py-6 text-center text-sm text-zinc-500">No notes yet.</div>
                        @endforelse
                    </div>

                    <div class="border-t border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <flux:textarea wire:model="newNote" rows="3" placeholder="Add a private note for the eldership…" />
                        <flux:error name="newNote" />
                        <div class="mt-2 flex justify-end">
                            <flux:button variant="primary" size="sm" wire:click="addNote">Save Note</flux:button>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Messages --}}
            @if ($person->user)
                <livewire:people.messages-panel :person="$person" :key="'dm-'.$person->id" />
            @endif
        </div>

        {{-- RIGHT: read-only profile --}}
        <flux:card class="overflow-hidden !p-0 lg:sticky lg:top-6" data-test="profile">
            <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <flux:icon icon="identification" class="size-5 text-zinc-500" />
                <flux:heading size="lg">Profile</flux:heading>
            </div>

            <div class="space-y-5 px-5 py-4">
                {{-- Contact --}}
                <div>
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Contact</p>
                    <dl>
                        <x-profile-row label="Email">
                            @if ($person->email)
                                <a href="mailto:{{ $person->email }}" class="text-emerald-700 underline underline-offset-2 dark:text-emerald-300">{{ $person->email }}</a>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </x-profile-row>
                        <x-profile-row label="Phone">{{ $person->phone ?: '—' }}</x-profile-row>
                        <x-profile-row label="Address">
                            @if ($person->address_line1 || $person->address_city)
                                <span class="block leading-relaxed">
                                    @if ($person->address_line1){{ $person->address_line1 }}<br>@endif
                                    {{ collect([$person->address_city, $person->address_state])->filter()->join(', ') }} {{ $person->address_zip }}
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </x-profile-row>
                    </dl>
                </div>

                {{-- Personal --}}
                <div>
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Personal</p>
                    <dl>
                        <x-profile-row label="Birthday">{{ $person->birth_date?->format('M j, Y') ?? '—' }}</x-profile-row>
                        <x-profile-row label="Gender">{{ $person->gender?->label() ?? '—' }}</x-profile-row>
                        <x-profile-row label="Baptized">
                            @if ($person->baptized)
                                <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300">
                                    <flux:icon icon="waves" class="size-4" />
                                    {{ $person->baptism_date?->format('M j, Y') ?? 'Yes' }}
                                </span>
                            @else
                                <span class="text-zinc-400">Not yet</span>
                            @endif
                        </x-profile-row>
                    </dl>
                </div>

                {{-- Household --}}
                <div>
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Household</p>
                    <dl>
                        <x-profile-row label="Household">{{ $person->household?->name ?? '—' }}</x-profile-row>
                        <x-profile-row label="Role">{{ $person->household_role?->label() ?? '—' }}</x-profile-row>
                    </dl>
                </div>

                {{-- Membership --}}
                <div>
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Membership</p>
                    <dl>
                        <x-profile-row label="Status">
                            <x-membership-badge :status="$person->membership_status" :reason="$person->termination_reason" />
                        </x-profile-row>
                        <x-profile-row label="Since">{{ $person->membership_since?->format('Y') ?? '—' }}</x-profile-row>
                    </dl>
                </div>

                {{-- Office --}}
                <div>
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Office</p>
                    <dl>
                        <x-profile-row label="Office">
                            @if ($person->offices->isNotEmpty())
                                <span class="inline-flex flex-wrap gap-1.5">
                                    @foreach ($person->offices as $office)
                                        <x-office-chip :kind="$office->kind" />
                                    @endforeach
                                </span>
                            @else
                                <span class="text-zinc-400">None held</span>
                            @endif
                        </x-profile-row>
                    </dl>
                </div>

                {{-- Pastoral care --}}
                @if ($this->canAccessPastoralCare)
                    <div>
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Pastoral care</p>
                        <dl>
                            <x-profile-row label="Shepherd">
                                @if ($person->pastoralCareElder)
                                    <span class="inline-flex items-center gap-2">
                                        <x-person-avatar :person="$person->pastoralCareElder" size="xs" />
                                        {{ $person->pastoralCareElder->full_name }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">Unassigned</span>
                                @endif
                            </x-profile-row>
                            <x-profile-row label="Prayer rota">
                                @if (isset($this->rota['label']))
                                    {{ $this->rota['label'] }}
                                @else
                                    <span class="text-zinc-400">{{ $this->rota['excluded'] }}</span>
                                @endif
                            </x-profile-row>
                        </dl>
                    </div>
                @endif
            </div>
        </flux:card>
    </div>

    <livewire:people.drawer />
</section>
