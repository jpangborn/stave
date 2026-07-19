<?php

use App\Models\Church;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $timezone = '';
    public ?string $email = '';
    public ?string $phone = '';
    public ?string $address = '';
    public ?string $website = '';

    public ?TemporaryUploadedFile $logo = null;

    public function mount(): void
    {
        $church = $this->church();

        abort_unless(Auth::user()->can('update', $church), 403);

        $this->name = $church->name;
        $this->timezone = $church->timezone;
        $this->email = $church->email;
        $this->phone = $church->phone;
        $this->address = $church->address;
        $this->website = $church->website;
    }

    public function updateChurch(): void
    {
        $church = $this->church();

        $this->authorize('update', $church);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $oldLogoPath = null;

        if ($this->logo instanceof TemporaryUploadedFile) {
            $path = $this->logo->store("churches/{$church->id}", 'digital-ocean');
            $oldLogoPath = $church->logo_path;
            $church->logo_path = $path;
            $this->logo = null;
        }

        $church->fill(collect($validated)->except('logo')->all())->save();

        if ($oldLogoPath) {
            Storage::disk('digital-ocean')->delete($oldLogoPath);
        }

        $this->dispatch('church-updated');
    }

    public function removeLogo(): void
    {
        $church = $this->church();

        $this->authorize('update', $church);

        if ($church->logo_path) {
            $oldPath = $church->logo_path;
            $church->forceFill(['logo_path' => null])->save();
            Storage::disk('digital-ocean')->delete($oldPath);
        }
    }

    private function church(): Church
    {
        return Auth::user()->currentChurch;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Church')" :subheading="__('Update your church\'s profile and contact information')">
        <form wire:submit="updateChurch" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Church name')" type="text" required />

            <flux:select wire:model="timezone" :label="__('Timezone')">
                @foreach (timezone_identifiers_list() as $tz)
                    <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="email" :label="__('Contact email')" type="email" />

            <flux:input wire:model="phone" :label="__('Phone')" type="text" />

            <flux:textarea wire:model="address" :label="__('Address')" rows="3" />

            <flux:input wire:model="website" :label="__('Website')" type="url" placeholder="https://example.org" />

            <flux:field>
                <flux:label>{{ __('Logo') }}</flux:label>

                @if (auth()->user()->currentChurch->logo_path)
                    <div class="mb-2 flex items-center gap-3">
                        <flux:avatar size="lg" :src="auth()->user()->currentChurch->logo_url" :name="auth()->user()->currentChurch->name" />
                        <flux:button size="sm" variant="subtle" wire:click="removeLogo">{{ __('Remove logo') }}</flux:button>
                    </div>
                @endif

                <flux:input type="file" wire:model="logo" accept="image/*" />
                <flux:error name="logo" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                <x-action-message class="me-3" on="church-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
