<?php

use App\Enums\DigestFrequency;
use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Services\NotificationPreferenceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    /** @var array<string, array<string, bool>> Keyed by enum name (e.g. 'COMMENT_MENTION' => ['WEBPUSH' => true, ...]) */
    public array $matrix = [];

    public ?string $quietHoursStart = null;

    public ?string $quietHoursEnd = null;

    public ?string $timezone = null;

    public string $digestFrequency = 'daily';

    public function mount(NotificationPreferenceService $preferences): void
    {
        $user = Auth::user();

        $this->quietHoursStart = $this->normalizeTime($user->quiet_hours_start);
        $this->quietHoursEnd = $this->normalizeTime($user->quiet_hours_end);
        $this->timezone = $user->timezone ?: config('app.timezone');
        $this->digestFrequency = ($user->digest_frequency ?? DigestFrequency::DAILY)->value;

        $valueMatrix = $preferences->matrixFor($user);

        foreach (NotificationEventType::userConfigurable() as $event) {
            foreach (NotificationChannel::cases() as $channel) {
                $this->matrix[$event->name][$channel->name] = $valueMatrix[$event->value][$channel->value];
            }
        }
    }

    public function updatedMatrix(mixed $value, string $key): void
    {
        if (! str_contains($key, '.')) {
            return;
        }

        [$eventName, $channelName] = explode('.', $key, 2);

        $event = $this->enumFromName(NotificationEventType::class, $eventName);
        $channel = $this->enumFromName(NotificationChannel::class, $channelName);

        if (! $event || ! $channel) {
            return;
        }

        if (! in_array($event, NotificationEventType::userConfigurable(), true)) {
            return;
        }

        app(NotificationPreferenceService::class)->setChannel(
            Auth::user(),
            $event,
            $channel,
            (bool) $value,
        );

        $this->dispatch('preference-saved');
    }

    public function saveQuietHours(): void
    {
        $validated = $this->validate([
            'quietHoursStart' => 'nullable|date_format:H:i|required_with:quietHoursEnd',
            'quietHoursEnd' => 'nullable|date_format:H:i|required_with:quietHoursStart',
            'timezone' => 'nullable|in:'.implode(',', \DateTimeZone::listIdentifiers()),
        ]);

        Auth::user()->forceFill([
            'quiet_hours_start' => $validated['quietHoursStart'] ?? null,
            'quiet_hours_end' => $validated['quietHoursEnd'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
        ])->save();

        $this->dispatch('quiet-hours-saved');
    }

    public function clearQuietHours(): void
    {
        $this->quietHoursStart = null;
        $this->quietHoursEnd = null;

        Auth::user()->forceFill([
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
        ])->save();

        $this->dispatch('quiet-hours-saved');
    }

    public function updatedDigestFrequency(string $value): void
    {
        $frequency = DigestFrequency::tryFrom($value);

        if (! $frequency) {
            return;
        }

        Auth::user()->forceFill(['digest_frequency' => $frequency->value])->save();

        $this->dispatch('digest-frequency-saved');
    }

    /** @return array<int, DigestFrequency> */
    public function getDigestFrequenciesProperty(): array
    {
        return DigestFrequency::cases();
    }

    /**
     * @return array<int, NotificationEventType>
     */
    public function getEventsProperty(): array
    {
        return NotificationEventType::userConfigurable();
    }

    /**
     * @return array<int, NotificationChannel>
     */
    public function getChannelsProperty(): array
    {
        return NotificationChannel::cases();
    }

    /**
     * @return array<int, string>
     */
    public function getTimezonesProperty(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enumClass
     * @return T|null
     */
    private function enumFromName(string $enumClass, string $name): ?object
    {
        return defined("$enumClass::$name") ? constant("$enumClass::$name") : null;
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
            return null;
        }

        return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
    }
}; ?>

<section class="w-full">
    <div class="space-y-8">
        <div>
            <flux:heading>{{ __('What to notify me about') }}</flux:heading>
            <flux:subheading>{{ __('Pick which events reach you on each channel.') }}</flux:subheading>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-zinc-500 dark:text-zinc-400">
                            <th class="py-2 pr-4 font-medium">{{ __('Event') }}</th>
                            @foreach ($this->channels as $channel)
                                <th class="px-3 py-2 text-center font-medium">{{ $channel->label() }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->events as $event)
                            <tr wire:key="event-{{ $event->name }}" class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="py-3 pr-4">
                                    <flux:text class="font-medium">{{ $event->label() }}</flux:text>
                                    <flux:subheading size="sm">{{ $event->description() }}</flux:subheading>
                                </td>
                                @foreach ($this->channels as $channel)
                                    <td class="px-3 py-3 text-center">
                                        <flux:switch
                                            wire:model.live="matrix.{{ $event->name }}.{{ $channel->name }}"
                                            data-test="pref-{{ $event->value }}-{{ $channel->value }}"
                                        />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-action-message class="mt-3 text-zinc-500 dark:text-zinc-400" on="preference-saved">
                {{ __('Saved.') }}
            </x-action-message>
        </div>

        <flux:separator />

        <form wire:submit="saveQuietHours" class="space-y-4">
            <div>
                <flux:heading>{{ __('Quiet hours') }}</flux:heading>
                <flux:subheading>
                    {{ __('Push and in-app banners pause during this window. @mentions still come through. Email is unaffected.') }}
                </flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <flux:input wire:model="quietHoursStart" type="time" :label="__('Start')" />
                <flux:input wire:model="quietHoursEnd" type="time" :label="__('End')" />
                <flux:select wire:model="timezone" :label="__('Time zone')" variant="listbox" searchable>
                    @foreach ($this->timezones as $tz)
                        <flux:select.option :value="$tz">{{ $tz }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <flux:button type="submit" variant="primary">{{ __('Save quiet hours') }}</flux:button>
                <flux:button wire:click="clearQuietHours" type="button" variant="ghost">
                    {{ __('Disable quiet hours') }}
                </flux:button>
                <x-action-message class="text-zinc-500 dark:text-zinc-400" on="quiet-hours-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <flux:separator />

        <div class="space-y-4">
            <div>
                <flux:heading>{{ __('Email digest') }}</flux:heading>
                <flux:subheading>
                    {{ __('Roll up email notifications instead of sending one per event. @mentions always send instantly. Daily sends at 07:00, weekly on Mondays at 07:00.') }}
                </flux:subheading>
            </div>

            <div class="max-w-xs">
                <flux:select
                    wire:model.live="digestFrequency"
                    :label="__('Digest frequency')"
                    data-test="digest-frequency"
                >
                    @foreach ($this->digestFrequencies as $frequency)
                        <flux:select.option :value="$frequency->value">{{ $frequency->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <x-action-message class="text-zinc-500 dark:text-zinc-400" on="digest-frequency-saved">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </div>
</section>
