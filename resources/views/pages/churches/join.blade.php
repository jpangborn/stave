<?php

use App\Models\Church;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Locked]
    public string $token = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->church();
    }

    /**
     * Join as the logged-in user. Join-link members get no access roles —
     * a congregation-wide QR code must not grant staff capabilities.
     */
    public function join(): void
    {
        $church = $this->church();
        $user = Auth::user();

        DB::transaction(function () use ($church, $user): void {
            $church->addMember($user);
            $user->switchChurch($church);
        });

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function goToLogin(): void
    {
        Redirect::setIntendedUrl(route('churches.join', $this->token));

        $this->redirect(route('login'), navigate: false);
    }

    public function goToRegister(): void
    {
        $this->redirect(route('register', ['join' => $this->token]), navigate: false);
    }

    public function church(): Church
    {
        return Church::query()->where('join_token', $this->token)->firstOrFail();
    }
}; ?>

@php
    $church = $this->church();
@endphp

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Join :church', ['church' => $church->name])"
        :description="__(':church uses Stave to plan worship services and stay connected.', ['church' => $church->name])"
    />

    @auth
        @if ($church->hasMember(auth()->user()))
            <flux:text class="text-center">{{ __('You are already a member of this church.') }}</flux:text>

            <flux:button variant="primary" class="w-full" :href="route('dashboard')">{{ __('Go to dashboard') }}</flux:button>
        @else
            <flux:button variant="primary" class="w-full" wire:click="join">
                {{ __('Join :church', ['church' => $church->name]) }}
            </flux:button>
        @endif
    @endauth

    @guest
        <flux:button variant="primary" class="w-full" wire:click="goToRegister">
            {{ __('Create an account') }}
        </flux:button>

        <flux:button variant="ghost" class="w-full" wire:click="goToLogin">
            {{ __('I already have an account') }}
        </flux:button>
    @endguest
</div>
