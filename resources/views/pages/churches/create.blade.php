<?php

use App\Enums\AccessRole;
use App\Models\Church;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';

    /**
     * Create a church for a user who has none (e.g. removed from their last
     * church) and make them its administrator.
     */
    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($user): void {
            $church = Church::create([
                'name' => $this->name,
                'slug' => Church::uniqueSlugFor($this->name),
            ]);

            $church->addMember($user, [AccessRole::ADMIN]);
            $user->switchChurch($church);
        });

        $this->redirect(route('dashboard'), navigate: false);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Set up your church')"
        :description="__('You are not a member of any church yet. Create one to get started, or ask a church administrator for an invitation.')"
    />

    <form wire:submit="create" class="flex flex-col gap-6">
        <flux:input
            wire:model="name"
            :label="__('Church name')"
            type="text"
            required
            autofocus
            :placeholder="__('Your church\'s name')"
        />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('Create church') }}
        </flux:button>
    </form>
</div>
