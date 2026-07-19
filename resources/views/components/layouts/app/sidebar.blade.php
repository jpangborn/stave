<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible persist class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand :href="route('dashboard')" name="Stave" class="font-bold">
                    <x-slot:logo class="size-8 rounded-md bg-accent-content text-accent-foreground">
                        <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
                    </x-slot:logo>
                </flux:sidebar.brand>
                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <livewire:sidebar.church-switcher />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:sidebar.item>

                <flux:sidebar.group expandable icon="lectern" :heading="__('Liturgy')">
                    <flux:sidebar.item icon="calendar-days" :href="route('services.index')" :current="request()->routeIs('services.*')" wire:navigate>{{ __('Services') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="notepad-text-dashed" :href="route('templates.index')" :current="request()->routeIs('templates.*')" wire:navigate>{{ __('Templates') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="musical-note" :href="route('songs.index')" :current="request()->routeIs('songs.*')" wire:navigate>{{ __('Songs') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" :href="route('readings.index')" :current="request()->routeIs('readings.*')" wire:navigate>{{ __('Readings') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="library-big" :href="route('series.index')" :current="request()->routeIs('series.*')" wire:navigate>{{ __('Series') }}</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable icon="church" :heading="__('Congregation')">
                    <flux:sidebar.item icon="users" :href="route('people.index')" :current="request()->routeIs('people.*')" wire:navigate>{{ __('People') }}</flux:sidebar.item>
                    @if (auth()->user()?->canAccessPastoralCare())
                        <flux:sidebar.item icon="heart" :href="route('pastoral-care.index')" :current="request()->routeIs('pastoral-care.*')" wire:navigate>{{ __('Pastoral Care') }}</flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="user-group" :href="route('groups.index')" :current="request()->routeIs('groups.*')" wire:navigate>{{ __('Groups') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="home-modern" :href="route('households.index')" :current="request()->routeIs('households.*')" wire:navigate>{{ __('Households') }}</flux:sidebar.item>
                    <livewire:sidebar.messages />
                    @if (auth()->user()?->canAccessPastoralCare())
                        <flux:sidebar.item icon="calendar-date-range" :href="route('prayer-schedule.index')" :current="request()->routeIs('prayer-schedule.*')" wire:navigate>{{ __('Prayer Schedule') }}</flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @can('update', auth()->user()?->currentChurch)
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('church.settings')" :current="request()->routeIs('church.settings')" wire:navigate>{{ __('Church Settings') }}</flux:sidebar.item>
                @endcan
                @can('manageMembers', auth()->user()?->currentChurch)
                    <flux:sidebar.item icon="envelope" :href="route('church.invitations')" :current="request()->routeIs('church.invitations')" wire:navigate>{{ __('Invitations') }}</flux:sidebar.item>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <livewire:sidebar.notifications />

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    :avatar="auth()->user()->gravatar"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <flux:avatar name="{{ auth()->user()->name }}" src="{{ auth()->user()->gravatar }}" />
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('ios-a2hs-banner')
            <x-ios-a2hs-banner />
        @endpersist

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
