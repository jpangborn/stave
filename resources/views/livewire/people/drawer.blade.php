<?php

use App\Enums\AccessRole;
use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Enums\Office;
use App\Livewire\Forms\PersonForm;
use App\Models\Person;
use App\Models\PersonOffice;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $personId = null;

    public bool $showElderPicker = false;

    public PersonForm $form;

    /** @var array<string, bool> */
    public array $accessRoles = [];

    /** @var array<string, bool> */
    #[Locked]
    public array $originalAccessRoles = [];

    #[Computed]
    public function person(): ?Person
    {
        if (! $this->personId) {
            return null;
        }

        return Person::with(['allOffices', 'user', 'pastoralCareElder', 'assignedCongregants'])->find($this->personId);
    }

    #[Computed]
    public function isElder(): bool
    {
        return (bool) $this->person?->allOffices->contains(fn (PersonOffice $o) => $o->kind === Office::ELDER && $o->ended_on === null);
    }

    /** @return \Illuminate\Support\Collection<int, Person> */
    #[Computed]
    public function elderCandidates()
    {
        return Person::query()
            ->whereHas('offices', fn ($q) => $q->where('kind', Office::ELDER))
            ->when($this->person, fn ($q) => $q->where('id', '!=', $this->person->id))
            ->orderBy('last_name')
            ->get();
    }

    #[On('open-person-drawer')]
    public function openPerson(int $personId): void
    {
        $this->personId = $personId;
        $this->showElderPicker = false;
        $this->loadForm();
        $this->loadAccessRoles();
        Flux::modal('person-drawer')->show();
    }

    public function toggleElderPicker(): void
    {
        $this->showElderPicker = ! $this->showElderPicker;
    }

    private function loadForm(): void
    {
        $person = $this->person;

        if ($person) {
            $this->form->setPerson($person);
        }
    }

    private function loadAccessRoles(): void
    {
        $this->accessRoles = [];
        $this->originalAccessRoles = [];

        if (! $this->person?->user) {
            return;
        }

        foreach (AccessRole::cases() as $role) {
            $active = $this->person->user->hasAccessRole($role);
            $this->accessRoles[$role->value] = $active;
            $this->originalAccessRoles[$role->value] = $active;
        }
    }

    public function save(): void
    {
        if (! $this->person) {
            return;
        }

        $this->form->update();

        $this->persistAccessRoles();

        Flux::toast(variant: 'success', text: 'Saved.');
        Flux::modal('person-drawer')->close();

        $this->dispatch('person-saved', personId: $this->person->id);
    }

    private function persistAccessRoles(): void
    {
        $user = $this->person?->user;

        if (! $user) {
            return;
        }

        foreach (AccessRole::cases() as $role) {
            $desired = (bool) ($this->accessRoles[$role->value] ?? false);
            $original = (bool) ($this->originalAccessRoles[$role->value] ?? false);

            if ($desired === $original) {
                continue;
            }

            $desired
                ? $user->grantAccessRole($role)
                : $user->revokeAccessRole($role);
        }
    }

    public function delete(): void
    {
        if (! $this->person) {
            return;
        }

        $id = $this->person->id;
        $this->person->delete();

        Flux::toast(variant: 'danger', text: 'Person deleted.');
        Flux::modal('person-drawer')->close();

        $this->personId = null;
        $this->form->person = null;
        unset($this->person);

        $this->dispatch('person-deleted', personId: $id);
    }

    public function addOffice(string $kind): void
    {
        if (! $this->person) {
            return;
        }

        $office = Office::tryFrom($kind);
        if (! $office) {
            return;
        }

        if ($this->person->offices->contains(fn (PersonOffice $o) => $o->kind === $office)) {
            return;
        }

        PersonOffice::create([
            'person_id' => $this->person->id,
            'kind' => $office,
            'started_on' => now()->toDateString(),
        ]);

        unset($this->person);

        Flux::toast(variant: 'success', text: $office->label().' assigned.');
    }

    public function endOffice(int $officeId, ?string $reason = null): void
    {
        if (! $this->person) {
            return;
        }

        $office = PersonOffice::where('person_id', $this->person->id)->findOrFail($officeId);
        $office->update([
            'ended_on' => now()->toDateString(),
            'end_reason' => $reason,
        ]);

        unset($this->person);

        Flux::toast(variant: 'success', text: 'Office ended.');
    }

}; ?>

<div>
    <flux:modal variant="flyout" name="person-drawer" class="w-2/5">
        @if ($this->person)
            @php($person = $this->person)

            <div class="space-y-1">
                <div class="flex items-start gap-3">
                    <x-person-avatar :person="$person" size="lg" />
                    <div class="flex-1 min-w-0">
                        <flux:heading size="lg">{{ $person->full_name }}</flux:heading>
                        @if ($person->email)
                            <a href="mailto:{{ $person->email }}" class="text-sm text-emerald-700 dark:text-emerald-300 underline underline-offset-2 break-all">{{ $person->email }}</a>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <x-membership-badge :status="$person->membership_status" :reason="$person->termination_reason" />
                            @foreach ($person->offices as $office)
                                <x-office-chip :kind="$office->kind" />
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <flux:separator class="my-5" />

            <form wire:submit="save" class="space-y-6">
                {{-- Contact --}}
                <section>
                    <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Contact</flux:heading>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label>First name</flux:label>
                            <flux:input wire:model="form.first_name" />
                            <flux:error name="form.first_name" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Last name</flux:label>
                            <flux:input wire:model="form.last_name" />
                            <flux:error name="form.last_name" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Email</flux:label>
                            <flux:input wire:model="form.email" type="email" icon="envelope" />
                            <flux:error name="form.email" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Phone</flux:label>
                            <flux:input wire:model="form.phone" icon="phone" placeholder="(555) 123-4567" />
                            <flux:error name="form.phone" />
                        </flux:field>
                    </div>
                    <flux:field class="mt-3">
                        <flux:label>Street address</flux:label>
                        <flux:input wire:model="form.address_line1" icon="map-pin" />
                    </flux:field>
                    <div class="grid grid-cols-[2fr_1fr_1fr] gap-3 mt-3">
                        <flux:field>
                            <flux:label>City</flux:label>
                            <flux:input wire:model="form.address_city" />
                        </flux:field>
                        <flux:field>
                            <flux:label>State</flux:label>
                            <flux:input wire:model="form.address_state" maxlength="2" />
                        </flux:field>
                        <flux:field>
                            <flux:label>ZIP</flux:label>
                            <flux:input wire:model="form.address_zip" />
                        </flux:field>
                    </div>
                </section>

                {{-- Membership --}}
                <section>
                    <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Membership</flux:heading>
                    <flux:radio.group wire:model.live="form.membership_status" variant="buttons" class="flex-col sm:grid sm:grid-cols-4">
                        @foreach (MembershipStatus::cases() as $status)
                            @if ($status !== MembershipStatus::TERMINATED)
                                <flux:radio
                                    :value="$status->value"
                                    :icon="$status->icon()"
                                    :label="$status->label()"
                                />
                            @endif
                        @endforeach
                    </flux:radio.group>

                    <flux:callout variant="secondary" inline class="mt-2">
                        <div class="flex items-center gap-x-2">
                            <flux:badge rounded size="sm" icon="{{ $form->person->membership_status->icon() }}" color="{{ $form->person->membership_status->color() }}">{{ $form->person->membership_status->label() }}</flux:badge>
                            @if($form->membership_since)
                            <flux:text inline size="sm">Since {{ $form->membership_since }}</flux:text>
                            @endif
                        </div>

                        <x-slot name="actions">
                            @if($form->membership_status === MembershipStatus::MEMBER)
                            <flux:button variant="ghost">Terminate...</flux:button>
                            @endif
                            <flux:date-picker wire:model="form.membership_since">
                                <x-slot name="trigger">
                                    <flux:button icon="calendar">Set Date</flux:button>
                                </x-slot>
                            </flux:date-picker>
                        </x-slot>
                    </flux:callout>
                </section>

                {{-- Pastoral Care --}}
                <section>
                    <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Pastoral care</flux:heading>
                    <flux:card class="!p-3 bg-white dark:bg-zinc-800 space-y-6">
                        {{-- Assigned elder --}}
                        @php($elder = $person->pastoralCareElder)
                        <div class="flex items-center gap-3">
                            @if ($elder)
                                <x-person-avatar :person="$elder" size="sm" />
                            @else
                                <div class="grid size-8 place-items-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400 shrink-0">
                                    <flux:icon icon="user" class="size-4" />
                                </div>
                            @endif
                            <div class="flex flex-col gap-1 min-w-0">
                                <flux:heading class="!text-sm truncate">
                                    {{ $elder?->full_name ?? 'No elder assigned' }}
                                </flux:heading>
                                @if ($elder && $elder->email)
                                    <p class="text-xs text-zinc-500 truncate">{{ $elder->email }}</p>
                                @else
                                    <p class="text-xs text-zinc-500">Pastoral care provided by an elder</p>
                                @endif
                            </div>
                            <flux:spacer />
                            @if ($elder)
                                <flux:button size="sm" variant="ghost" wire:click="toggleElderPicker">
                                    Change
                                </flux:button>
                            @else
                                <flux:button size="sm" variant="ghost" icon="plus" wire:click="toggleElderPicker">
                                    Assign
                                </flux:button>
                            @endif
                        </div>

                        @if ($showElderPicker)
                            <flux:select
                                variant="listbox"
                                searchable
                                wire:model.live="form.pastoral_care_elder_id"
                                placeholder="Search elders..."
                                clearable
                            >
                                @foreach ($this->elderCandidates as $candidate)
                                    <flux:select.option :value="$candidate->id">
                                        {{ $candidate->full_name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif

                        {{-- Congregants (only when this person is an elder) --}}
                        @if ($this->isElder)
                            @php($congregants = $person->assignedCongregants)
                            @php($count = $congregants->count())
                            <div class="flex items-center gap-3">
                                <div class="grid size-8 place-items-center rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300 shrink-0">
                                    <flux:icon icon="users" class="size-4" />
                                </div>
                                <div class="flex flex-col gap-1 min-w-0">
                                    <flux:heading class="!text-sm">
                                        @if ($count === 0)
                                            No congregants assigned
                                        @else
                                            {{ $count }} {{ Str::plural('congregant', $count) }} assigned to {{ $person->first_name }}
                                        @endif
                                    </flux:heading>
                                    @if ($count > 0)
                                        <div class="flex -space-x-1.5">
                                            @foreach ($congregants->take(5) as $congregant)
                                                <x-person-avatar :person="$congregant" size="xs" circle class="ring-2 ring-white dark:ring-zinc-900" />
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-zinc-500">Members under this elder's care will appear here</p>
                                    @endif
                                </div>
                                <flux:spacer />
                                @if ($count > 0)
                                    <flux:modal.trigger name="pastoral-care-congregants">
                                        <flux:button size="sm" variant="ghost">View all</flux:button>
                                    </flux:modal.trigger>
                                @endif
                            </div>
                        @endif
                    </flux:card>
                </section>

                {{-- Office --}}
                <section>
                    <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Office</flux:heading>
                    <flux:card class="!p-3 bg-zinc-50 dark:bg-zinc-700 space-y-6">
                        @foreach (Office::cases() as $kind)
                            @php($held = $person->offices->firstWhere('kind', $kind))
                            <div class="flex items-center gap-3">
                                <flux:icon :icon="$kind->icon()" class="size-5 shrink-0 mt-0.5 {{ $kind->textColorClass() }}" />
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <flux:heading class="!text-sm">{{ $kind->label() }}</flux:heading>
                                        @if ($held)
                                            <flux:badge size="sm" color="zinc">Held</flux:badge>
                                        @endif
                                    </div>
                                    @if ($held)
                                        <p class="text-xs text-zinc-500">Since {{ $held->started_on->format('M Y') }}</p>
                                    @else
                                        <p class="text-xs text-zinc-500">{{ $kind->description() }}</p>
                                    @endif
                                </div>
                                <flux:spacer />
                                @if ($held)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="endOffice({{ $held->id }})"
                                        wire:confirm="End {{ $kind->label() }} office?"
                                    >
                                        Step down
                                    </flux:button>
                                @else
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="plus"
                                        wire:click="addOffice('{{ $kind->value }}')"
                                    >
                                        Assign
                                    </flux:button>
                                @endif
                            </div>
                        @endforeach
                    </flux:card>

                    @if ($person->formerOffices->isNotEmpty())
                        <div class="mt-3">
                            <flux:subheading class="!text-xs">Former</flux:subheading>
                            <ul class="space-y-1 mt-1">
                                @foreach ($person->formerOffices as $office)
                                    <li class="flex items-center gap-2 text-xs text-zinc-500">
                                        <x-office-chip :kind="$office->kind" size="sm" />
                                        <span>{{ $office->started_on->format('Y') }} – {{ $office->ended_on->format('Y') }}</span>
                                        @if ($office->end_reason)
                                            <span class="text-zinc-400">· {{ $office->end_reason }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>

                {{-- Access --}}
                @if ($person->user)
                    <section>
                        <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Access</flux:heading>
                        <flux:card class="!p-3 bg-zinc-50 dark:bg-zinc-700 space-y-4">
                            {{-- Person baseline (informational, always on) --}}
                            <div class="flex items-center gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100">
                                    <flux:icon icon="users" class="size-5 text-zinc-600" />
                                </div>
                                <div class="flex flex-col gap-0.5 min-w-0">
                                    <flux:heading class="!text-sm">Person</flux:heading>
                                    <p class="text-xs text-zinc-500">Default access. Can view and request to join groups.</p>
                                </div>
                                <flux:spacer />
                                <flux:checkbox checked disabled />
                            </div>

                            @foreach (AccessRole::groupedForDisplay() as $group => $roles)
                                <div class="space-y-3">
                                    <flux:subheading class="!text-xs uppercase tracking-wider text-zinc-500">{{ $group }}</flux:subheading>
                                    @foreach ($roles as $role)
                                        <div class="flex items-center gap-3">
                                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $role->iconBgClass() }}">
                                                <flux:icon :icon="$role->icon()" class="size-5 {{ $role->iconColorClass() }}" />
                                            </div>
                                            <div class="flex flex-col gap-0.5 min-w-0">
                                                <flux:heading class="!text-sm">{{ $role->label() }}</flux:heading>
                                                <p class="text-xs text-zinc-500">{{ $role->description() }}</p>
                                            </div>
                                            <flux:spacer />
                                            <flux:checkbox wire:model="accessRoles.{{ $role->value }}" />
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </flux:card>

                        <p class="mt-2 flex items-center gap-1.5 text-xs text-zinc-500">
                            <flux:icon icon="shield-check" class="size-3.5" />
                            Administrators have everything above included.
                        </p>
                    </section>
                @endif

                {{-- Groups --}}
                @if ($person->user && $person->user->groups->isNotEmpty())
                    <section>
                        <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Groups</flux:heading>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($person->user->groups as $group)
                                <flux:badge size="sm" color="zinc" icon="user-group">{{ $group->name }}</flux:badge>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Activity --}}
                <section>
                    <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Activity</flux:heading>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
                        <dt class="text-zinc-500">Last active</dt>
                        <dd>{{ $person->last_active_at?->diffForHumans() ?? '—' }}</dd>
                        <dt class="text-zinc-500">Added</dt>
                        <dd>{{ $person->created_at->toFormattedDayDateString() }}</dd>
                        <dt class="text-zinc-500">Gender</dt>
                        <dd>{{ $person->gender?->label() ?? '—' }}</dd>
                    </dl>
                </section>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button type="button" variant="ghost" icon="trash" class="!text-rose-600" wire:click="delete" wire:confirm="Permanently delete this person?">
                        Delete person
                    </flux:button>
                    <div class="flex gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost" type="button">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
                    </div>
                </div>
            </form>

            @if ($this->isElder)
                <flux:modal name="pastoral-care-congregants" class="w-md">
                    <div class="space-y-4">
                        <div>
                            <flux:heading size="lg">Congregants assigned to {{ $person->first_name }}</flux:heading>
                            <flux:subheading>Pastoral care responsibilities</flux:subheading>
                        </div>
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($person->assignedCongregants as $congregant)
                                <li class="flex items-center gap-3 py-2">
                                    <x-person-avatar :person="$congregant" size="sm" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium">{{ $congregant->full_name }}</p>
                                        @if ($congregant->email)
                                            <p class="text-xs text-zinc-500 truncate">{{ $congregant->email }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </flux:modal>
            @endif
        @endif
    </flux:modal>
</div>
