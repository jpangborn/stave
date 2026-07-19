<?php

use App\Models\ChurchInvitation;
use App\Models\Person;
use App\Models\User;
use App\Support\CurrentChurch;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Locked]
    public string $token = '';

    public string $name = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $invitation = $this->invitation();

        abort_unless($invitation->isPending(), 404);
    }

    /**
     * Accept as the logged-in user whose email matches the invitation.
     */
    public function join(): void
    {
        $invitation = $this->invitation();

        abort_unless($invitation->isPending(), 404);

        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless(Str::lower($user->email) === Str::lower($invitation->email), 403);

        DB::transaction(function () use ($invitation, $user): void {
            $invitation->church->addMember($user, $invitation->accessRoles());
            $user->switchChurch($invitation->church);
            $invitation->forceFill(['accepted_at' => now()])->save();
        });

        $this->redirect(route('dashboard'), navigate: false);
    }

    /**
     * Accept as a brand-new user: clicking the emailed link proves mailbox
     * ownership, so the account is created already verified.
     */
    public function register(): void
    {
        $invitation = $this->invitation();

        abort_unless($invitation->isPending(), 404);
        abort_if(Auth::check(), 403);
        abort_if(User::query()->where('email', $invitation->email)->exists(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated): User {
            $person = app(CurrentChurch::class)->runAs($invitation->church, function () use ($invitation, $validated): Person {
                [$first, $last] = array_pad(preg_split('/\s+/', trim($validated['name']), 2), 2, '');

                return Person::firstOrCreate([
                    'email' => Str::lower($invitation->email),
                ], [
                    'first_name' => $first,
                    'last_name' => $last,
                ]);
            });

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => $validated['password'],
                'person_id' => $person->id,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->church->addMember($user, $invitation->accessRoles());
            $user->forceFill(['current_church_id' => $invitation->church_id])->save();

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function goToLogin(): void
    {
        Redirect::setIntendedUrl(route('invitations.accept', $this->token));

        $this->redirect(route('login'), navigate: false);
    }

    public function invitation(): ChurchInvitation
    {
        return ChurchInvitation::query()
            ->where('token', $this->token)
            ->with('church')
            ->firstOrFail();
    }
}; ?>

@php
    $invitation = $this->invitation();
    $church = $invitation->church;
    $existingUser = App\Models\User::query()->where('email', $invitation->email)->exists();
    $emailMatches = auth()->check() && Illuminate\Support\Str::lower(auth()->user()->email) === Illuminate\Support\Str::lower($invitation->email);
@endphp

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Join :church', ['church' => $church->name])"
        :description="__('You have been invited to join :church on Stave.', ['church' => $church->name])"
    />

    @auth
        @if ($emailMatches)
            <flux:button variant="primary" class="w-full" wire:click="join">
                {{ __('Accept invitation') }}
            </flux:button>
        @else
            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.text>
                    {{ __('This invitation was sent to :email, but you are signed in as :current. Log out first to accept it.', ['email' => $invitation->email, 'current' => auth()->user()->email]) }}
                </flux:callout.text>
            </flux:callout>
        @endif
    @endauth

    @guest
        @if ($existingUser)
            <flux:text class="text-center">
                {{ __('This invitation was sent to :email. Log in with that account to accept it.', ['email' => $invitation->email]) }}
            </flux:text>

            <flux:button variant="primary" class="w-full" wire:click="goToLogin">
                {{ __('Log in to accept') }}
            </flux:button>
        @else
            <form wire:submit="register" class="flex flex-col gap-6">
                <flux:input :label="__('Email address')" type="email" :value="$invitation->email" disabled />

                <flux:input
                    wire:model="name"
                    :label="__('Name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="__('Full name')"
                />

                <flux:input
                    wire:model="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    viewable
                />

                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    viewable
                />

                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Create account & join') }}
                </flux:button>
            </form>
        @endif
    @endguest
</div>
