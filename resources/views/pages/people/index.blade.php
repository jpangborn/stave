<?php

use App\Enums\MembershipStatus;
use App\Models\Person;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    #[Session('people.layout')]
    public string $layout = 'table';

    #[Session('people.density')]
    public string $density = 'spacious';

    public ?int $openPersonId = null;

    public function openPerson(int $id): void
    {
        $this->openPersonId = $id;
        $this->dispatch('open-person-drawer', personId: $id);
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function setLayout(string $layout): void
    {
        $this->layout = in_array($layout, ['table', 'cards'], true) ? $layout : 'table';
        $this->resetPage();
    }

    public function setDensity(string $density): void
    {
        $this->density = in_array($density, ['spacious', 'compact'], true) ? $density : 'spacious';
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
            ->when($this->filter !== 'all', fn ($q) => $q->where('membership_status', $this->filter))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($this->layout === 'cards' ? 24 : 12);
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
        <flux:radio.group variant="segmented" size="sm" wire:model.live="filter">
            <flux:radio value="all" icon="users">
                All <flux:badge size="sm" class="ms-1">{{ $this->counts['all'] ?? 0 }}</flux:badge>
            </flux:radio>
            <flux:radio value="member" icon="home-modern">
                Members <flux:badge size="sm" class="ms-1">{{ $this->counts['member'] ?? 0 }}</flux:badge>
            </flux:radio>
            <flux:radio value="catechumen" icon="book-open">
                Catechumens <flux:badge size="sm" class="ms-1">{{ $this->counts['catechumen'] ?? 0 }}</flux:badge>
            </flux:radio>
            <flux:radio value="adherent" icon="user-group">
                Adherents <flux:badge size="sm" class="ms-1">{{ $this->counts['adherent'] ?? 0 }}</flux:badge>
            </flux:radio>
            <flux:radio value="visitor" icon="face-smile">
                Visitors <flux:badge size="sm" class="ms-1">{{ $this->counts['visitor'] ?? 0 }}</flux:badge>
            </flux:radio>
        </flux:radio.group>

        <div class="flex items-center gap-2">
            <flux:input
                wire:model.live.debounce.250ms="search"
                size="sm"
                placeholder="Search by name, email, phone…"
                icon="magnifying-glass"
                class="w-72"
                clearable
            />

            <flux:button.group size="sm">
                <flux:button
                    icon="bars-3"
                    :variant="$layout === 'table' ? 'filled' : 'outline'"
                    wire:click="setLayout('table')"
                    title="Table view"
                />
                <flux:button
                    icon="squares-2x2"
                    :variant="$layout === 'cards' ? 'filled' : 'outline'"
                    wire:click="setLayout('cards')"
                    title="Card view"
                />
            </flux:button.group>

            <flux:button.group size="sm">
                <flux:button
                    icon="arrows-up-down"
                    :variant="$density === 'spacious' ? 'filled' : 'outline'"
                    wire:click="setDensity('spacious')"
                    title="Spacious"
                />
                <flux:button
                    icon="bars-2"
                    :variant="$density === 'compact' ? 'filled' : 'outline'"
                    wire:click="setDensity('compact')"
                    title="Compact"
                />
            </flux:button.group>
        </div>
    </div>

    @if ($this->people->isEmpty())
        @if ($search || $filter !== 'all')
            @include('livewire.people.partials.no-match')
        @else
            @include('livewire.people.partials.empty-state')
        @endif
    @elseif ($layout === 'cards')
        <div @class([
            'grid gap-3' => true,
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' => $density === 'spacious',
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5' => $density === 'compact',
        ])>
            @foreach ($this->people as $person)
                <livewire:people.card :$person :density="$density" :key="'card-'.$person->id" />
            @endforeach
        </div>

        <div class="mt-4">
            {{ $this->people->links() }}
        </div>
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
                    <livewire:people.row :$person :density="$density" :key="'row-'.$person->id" />
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <livewire:people.drawer :person-id="$openPersonId" />
    <livewire:people.add-modal />
</section>
