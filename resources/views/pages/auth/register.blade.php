<?php

use App\Enums\AccessRole;
use App\Models\Church;
use App\Models\Person;
use App\Models\User;
use App\Support\CurrentChurch;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $church_name = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /** Join-link token: registering through it joins that church instead of creating one. */
    #[Locked]
    public string $join = '';

    public function mount(): void
    {
        $this->join = (string) request()->query('join', '');
    }

    /**
     * Handle an incoming registration request: creates a new church with the
     * registrant as its administrator, or — when arriving through a church's
     * join link — joins that church as a plain member.
     */
    public function register(): void
    {
        $joinChurch = $this->joinChurch();

        $validated = $this->validate(array_filter([
            'church_name' => $joinChurch instanceof Church ? null : ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]));

        $user = DB::transaction(function () use ($validated, $joinChurch) {
            $church = $joinChurch ?? Church::create([
                'name' => $validated['church_name'],
                'slug' => Church::uniqueSlugFor($validated['church_name']),
            ]);

            // Make the church current so created records (the Person below)
            // are stamped with it.
            app(CurrentChurch::class)->set($church);

            [$first, $last] = array_pad(preg_split('/\s+/', trim($validated['name']), 2), 2, '');

            $person = Person::firstOrCreate([
                'email' => $validated['email']
            ], [
                'first_name' => $first,
                'last_name'  => $last,
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'person_id' => $person->id,
            ]);

            $church->users()->syncWithoutDetaching([$user->id => ['person_id' => $person->id]]);
            $user->forceFill(['current_church_id' => $church->id])->save();

            // Join-link members get no staff roles; church creators are admins.
            if (! $joinChurch instanceof Church) {
                $user->grantAccessRole(AccessRole::ADMIN, $church);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }

    public function joinChurch(): ?Church
    {
        return $this->join !== ''
            ? Church::query()->where('join_token', $this->join)->first()
            : null;
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        @if ($this->joinChurch())
            <flux:callout icon="church" variant="secondary">
                <flux:callout.text>
                    {{ __('You are joining :church.', ['church' => $this->joinChurch()->name]) }}
                </flux:callout.text>
            </flux:callout>
        @else
            <!-- Church Name -->
            <flux:input
                wire:model="church_name"
                :label="__('Church name')"
                type="text"
                required
                autofocus
                :placeholder="__('Your church\'s name')"
                :description="__('This creates a new church workspace with you as its administrator.')"
            />
        @endif

        <!-- Name -->
        <flux:input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autocomplete="name"
            :placeholder="__('Full name')"
        />

        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->
        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
            viewable
        />

        <!-- Confirm Password -->
        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
            viewable
        />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Already have an account?') }}
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
