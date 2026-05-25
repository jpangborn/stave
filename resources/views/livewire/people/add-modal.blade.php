<?php

use App\Livewire\Forms\PersonForm;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public PersonForm $form;

    public function save(bool $thenOpen = false): void
    {
        $person = $this->form->store();

        $this->form->reset();
        Flux::modal('add-person')->close();
        Flux::toast(variant: 'success', text: "Added {$person->full_name}.");

        $this->dispatch('person-added', personId: $person->id);

        if ($thenOpen) {
            $this->dispatch('open-person-drawer', personId: $person->id)->to('people.drawer');
        }
    }
}; ?>

<div>
    <flux:modal name="add-person" class="w-[28rem]">
        <form wire:submit="save" class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="grid size-10 place-items-center rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-100 dark:ring-emerald-900 shrink-0">
                    <flux:icon name="user-plus" class="size-5" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">Add a person</flux:heading>
                    <flux:subheading>Start with the basics. You can fill in phone, address, and access in the profile.</flux:subheading>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>First name</flux:label>
                    <flux:input wire:model="form.first_name" autofocus placeholder="Jane" />
                    <flux:error name="form.first_name" />
                </flux:field>
                <flux:field>
                    <flux:label>Last name</flux:label>
                    <flux:input wire:model="form.last_name" placeholder="Doe" />
                    <flux:error name="form.last_name" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="form.email" type="email" icon="envelope" placeholder="jane.doe@example.com" />
                <flux:error name="form.email" />
            </flux:field>

            <div class="flex items-center justify-between gap-2 pt-1">
                <flux:button variant="ghost" wire:click="save(true)" type="button">Save &amp; open profile →</flux:button>

                <div class="flex gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Add Person</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
