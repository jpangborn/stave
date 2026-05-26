<?php

use App\Enums\AccessRole;
use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Enums\Office;
use App\Livewire\Forms\PersonForm;
use App\Models\Person;
use App\Models\PersonOffice;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $personId = null;

    public PersonForm $form;

    public ?string $newOffice = null;

    public string $newOfficeStartedOn;

    public function mount(): void
    {
        $this->newOfficeStartedOn = now()->toDateString();
    }

    #[Computed]
    public function person(): ?Person
    {
        if (! $this->personId) {
            return null;
        }

        return Person::with(['allOffices', 'user', 'pastoralCareElder'])->find($this->personId);
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
        $this->loadForm();
        Flux::modal('person-drawer')->show();
    }

    private function loadForm(): void
    {
        $person = $this->person;

        if ($person) {
            $this->form->setPerson($person);
        }
    }

    public function save(): void
    {
        if (! $this->person) {
            return;
        }

        $this->form->update();

        Flux::toast(variant: 'success', text: 'Saved.');
        Flux::modal('person-drawer')->close();

        $this->dispatch('person-saved', personId: $this->person->id);
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
        $this->dispatch('person-deleted', personId: $id);
    }

    public function addOffice(): void
    {
        if (! $this->person) {
            return;
        }

        $this->validate([
            'newOffice' => ['required', Rule::enum(Office::class)],
            'newOfficeStartedOn' => ['required', 'date'],
        ]);

        PersonOffice::create([
            'person_id' => $this->person->id,
            'kind' => $this->newOffice,
            'started_on' => $this->newOfficeStartedOn,
        ]);

        $this->newOffice = null;
        $this->newOfficeStartedOn = now()->toDateString();

        unset($this->person);

        Flux::toast(variant: 'success', text: 'Office added.');
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

    public function toggleAccessRole(string $role): void
    {
        if (! $this->person?->user) {
            return;
        }

        $user = $this->person->user;
        $accessRole = AccessRole::tryFrom($role);
        if (! $accessRole) {
            return;
        }

        $user->hasAccessRole($accessRole)
            ? $user->revokeAccessRole($accessRole)
            : $user->grantAccessRole($accessRole);

        unset($this->person);

        Flux::toast(variant: 'success', text: 'Access updated.');
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
                            <flux:badge size="sm" icon="{{ $form->person->membership_status->icon() }}" color="{{ $form->person->membership_status->color() }}">{{ $form->person->membership_status->label() }}</flux:badge>
                            @if($form->membership_since)
                            <flux:text inline size="sm">Since {{ $form->membership_since }}</flux:text>
                            @endif
                        </div>

                        <x-slot name="actions">
                            <flux:button variant="ghost">Terminate...</flux:button>
                            <flux:date-picker wire:model="form.membership_since">
                                <x-slot name="trigger">
                                    <flux:button icon="calendar">Set Date</flux:button>
                                </x-slot>
                            </flux:date-picker>
                        </x-slot>
                    </flux:callout>
                </section>

                {{-- Office --}}
                <section>
                    <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Office</flux:heading>

                    @if ($person->offices->isEmpty())
                        <p class="text-sm text-zinc-500">No current office.</p>
                    @else
                        <ul class="space-y-1.5 mb-3">
                            @foreach ($person->offices as $office)
                                <li class="flex items-center justify-between gap-3 rounded-md ring-1 ring-zinc-200 dark:ring-zinc-700 px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <x-office-chip :kind="$office->kind" />
                                        <span class="text-xs text-zinc-500">since {{ $office->started_on->format('M Y') }}</span>
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="endOffice({{ $office->id }})" wire:confirm="End this office?">End</flux:button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="flex items-end gap-2">
                        <flux:field class="flex-1">
                            <flux:label>Add office</flux:label>
                            <flux:select wire:model="newOffice" variant="listbox" placeholder="Select…">
                                @foreach (Office::cases() as $kind)
                                    <flux:select.option :value="$kind->value">{{ $kind->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Started</flux:label>
                            <flux:input wire:model="newOfficeStartedOn" type="date" />
                        </flux:field>
                        <flux:button type="button" variant="ghost" icon="plus" wire:click="addOffice">Add</flux:button>
                    </div>

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

                {{-- Pastoral Care --}}
                <section>
                    <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Pastoral care</flux:heading>
                    <flux:select wire:model="form.pastoral_care_elder_id" variant="listbox" placeholder="Unassigned">
                        <flux:select.option :value="null">Unassigned</flux:select.option>
                        @foreach ($this->elderCandidates as $elder)
                            <flux:select.option :value="$elder->id">{{ $elder->full_name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </section>

                {{-- Access --}}
                @if ($person->user)
                    <section>
                        <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500 mb-2">Access</flux:heading>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (AccessRole::cases() as $role)
                                @php($active = $person->user->hasAccessRole($role))
                                <button type="button" wire:click="toggleAccessRole('{{ $role->value }}')" class="appearance-none">
                                    <flux:badge
                                        :color="$active ? $role->color() : 'zinc'"
                                        :icon="$role->icon()"
                                        size="sm"
                                        :title="$role->description()"
                                        :class="$active ? '' : 'opacity-50'"
                                    >
                                        {{ $role->label() }}
                                    </flux:badge>
                                </button>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">Click to toggle. (Display-only for now — no enforcement yet.)</p>
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
        @endif
    </flux:modal>
</div>
