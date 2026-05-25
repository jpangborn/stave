<?php

use App\Enums\MembershipStatus;
use App\Models\Person;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $filter = null;

    public ?int $openPersonId = null;

    public function openPerson(int $id): void
    {
        $this->openPersonId = $id;
        $this->dispatch('open-person-drawer', personId: $id);
    }

    public function setFilter(?string $filter): void
    {
        $this->filter = $filter === 'all' ? null : $filter;
        $this->resetPage();
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        $base = Person::query()->searchedBy($this->search);

        $byStatus = (clone $base)
            ->selectRaw('membership_status, count(*) as total')
            ->groupBy('membership_status')
            ->pluck('total', 'membership_status')
            ->all();

        return [
            'all' => (clone $base)->count(),
            'member' => (int) ($byStatus[MembershipStatus::MEMBER->value] ?? 0),
            'catechumen' => (int) ($byStatus[MembershipStatus::CATECHUMEN->value] ?? 0),
            'adherent' => (int) ($byStatus[MembershipStatus::ADHERENT->value] ?? 0),
            'visitor' => (int) ($byStatus[MembershipStatus::VISITOR->value] ?? 0),
        ];
    }

    #[Computed]
    public function people()
    {
        return Person::query()
            ->with(['offices', 'user'])
            ->searchedBy($this->search)
            ->when($this->filter, fn ($q) => $q->where('membership_status', $this->filter))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(12);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4 mb-4">
        <div>
            <flux:heading size="xl" level="1">People</flux:heading>
            <flux:subheading size="lg">Manage your members and visitors.</flux:subheading>
        </div>
        <flux:modal.trigger name="add-person">
            <flux:button size="sm" variant="primary" icon="plus">Add Person</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        @include('livewire.people.partials.filter-chips', ['counts' => $this->counts, 'current' => $filter])

        <div class="flex items-center gap-2">
            <flux:input
                wire:model.live.debounce.250ms="search"
                size="sm"
                placeholder="Search by name, email, phone…"
                icon="magnifying-glass"
                class="w-72"
                clearable
            />
        </div>
    </div>

    @if ($this->people->isEmpty())
        @if ($search || $filter)
            @include('livewire.people.partials.no-match')
        @else
            @include('livewire.people.partials.empty-state')
        @endif
    @else
        <flux:table :paginate="$this->people">
            <flux:table.columns>
                <flux:table.column>Person</flux:table.column>
                <flux:table.column>Membership</flux:table.column>
                <flux:table.column>Office</flux:table.column>
                <flux:table.column>Added</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->people as $person)
                    <livewire:people.row :$person :key="'row-'.$person->id" />
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <livewire:people.drawer :person-id="$openPersonId" />
    <livewire:people.add-modal />
</section>
