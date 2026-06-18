<?php

use App\Models\Household;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $hhName = '';

    #[Validate('nullable|string|max:255')]
    public ?string $hhAddress = null;

    /** @return Collection<int, Household> */
    #[Computed]
    public function households(): Collection
    {
        return Household::with(['people' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name')])
            ->orderBy('name')
            ->get();
    }

    public function openNew(): void
    {
        $this->reset('hhName', 'hhAddress', 'editingId');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function editHousehold(int $id): void
    {
        $household = Household::findOrFail($id);

        $this->editingId = $household->id;
        $this->hhName = $household->name;
        $this->hhAddress = $household->address;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->reset('hhName', 'hhAddress', 'editingId', 'showForm');
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => trim($this->hhName),
            'address' => $this->hhAddress ? trim($this->hhAddress) : null,
        ];

        if ($this->editingId) {
            Household::findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: 'Household updated.');
        } else {
            Household::create($data);
            Flux::toast(variant: 'success', text: 'Household created.');
        }

        unset($this->households);
        $this->cancelForm();
    }

    public function deleteHousehold(int $id): void
    {
        Household::findOrFail($id)->delete();

        unset($this->households);
        Flux::toast(variant: 'danger', text: 'Household deleted.');
    }

    public function openPerson(int $id): void
    {
        $this->dispatch('open-person-drawer', personId: $id)->to('people.drawer');
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4 mb-4">
        <div>
            <flux:heading size="xl" level="1">Households</flux:heading>
            <flux:subheading size="lg">Group people into households for directories, pastoral visits, and mailings.</flux:subheading>
        </div>
        <flux:button size="sm" variant="primary" icon="plus" wire:click="openNew">New Household</flux:button>
    </div>

    @if ($showForm)
        <flux:card class="mb-6">
            <form wire:submit="save" class="space-y-4">
                <flux:heading class="!text-xs uppercase tracking-wider text-zinc-500">
                    {{ $editingId ? 'Edit household' : 'New household' }}
                </flux:heading>
                <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr]">
                    <flux:field>
                        <flux:label>Household name</flux:label>
                        <flux:input wire:model="hhName" placeholder="e.g. The Baker Household" autofocus />
                        <flux:error name="hhName" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Home address <flux:text size="sm" class="text-zinc-400">(optional)</flux:text></flux:label>
                        <flux:input wire:model="hhAddress" placeholder="128 Elm Street, Lexington" />
                        <flux:error name="hhAddress" />
                    </flux:field>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" type="button" wire:click="cancelForm">Cancel</flux:button>
                    <flux:button variant="primary" type="submit">{{ $editingId ? 'Save' : 'Create household' }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($this->households->isEmpty())
        <flux:callout icon="home-modern" class="max-w-lg">
            <flux:callout.heading>No households yet</flux:callout.heading>
            <flux:callout.text>Create a household to group members for directories, pastoral visits, and mailings.</flux:callout.text>
        </flux:callout>
    @else
        <div class="grid gap-4 grid-cols-[repeat(auto-fill,minmax(330px,1fr))]">
            @foreach ($this->households as $household)
                <flux:card>
                    <div class="flex items-center gap-3">
                        <div class="grid size-[42px] place-items-center rounded-[10px] bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300 shrink-0">
                            <flux:icon icon="home-modern" class="size-5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:heading class="!text-base truncate">{{ $household->name }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-500 truncate">
                                @if ($household->address){{ $household->address }} · @endif{{ $household->people->count() }} {{ Str::plural('member', $household->people->count()) }}
                            </flux:text>
                        </div>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="editHousehold({{ $household->id }})">Edit</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="deleteHousehold({{ $household->id }})"
                                    wire:confirm="Delete this household? Members will be unassigned but not deleted."
                                >Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    @if ($household->people->isNotEmpty())
                        <flux:separator class="my-3.5" />
                        <div class="space-y-0.5">
                            @foreach ($household->people as $member)
                                <button
                                    type="button"
                                    wire:click="openPerson({{ $member->id }})"
                                    class="flex w-full items-center gap-3 rounded-lg px-1.5 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                >
                                    <x-person-avatar :person="$member" size="xs" />
                                    <span class="flex-1 min-w-0 truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $member->full_name }}</span>
                                    @if ($member->household_role)
                                        <flux:badge size="sm" color="zinc">{{ $member->household_role->label() }}</flux:badge>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @endforeach
        </div>
    @endif

    <livewire:people.drawer :person-id="null" />
</section>
