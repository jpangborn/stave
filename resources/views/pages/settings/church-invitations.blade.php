<?php

use App\Enums\AccessRole;
use App\Mail\ChurchInvitationMail;
use App\Models\Church;
use App\Models\ChurchInvitation;
use App\Support\QrCode;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    /** @var array<int, string> */
    public array $roles = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('manageMembers', $this->church()), 403);
    }

    public function invite(): void
    {
        $church = $this->church();

        $this->authorize('manageMembers', $church);

        $this->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'roles' => ['array'],
            'roles.*' => ['string', Illuminate\Validation\Rule::enum(AccessRole::class)],
        ]);

        $email = Str::lower($this->email);

        $alreadyMember = $church->users()->where('email', $email)->exists();

        if ($alreadyMember) {
            $this->addError('email', __('That person is already a member of this church.'));

            return;
        }

        $invitation = ChurchInvitation::createFor(
            $church,
            $email,
            array_map(fn (string $value): AccessRole => AccessRole::from($value), $this->roles),
            Auth::user(),
        );

        Mail::to($email)->send(new ChurchInvitationMail($invitation));

        $this->reset('email', 'roles');
        unset($this->invitations);

        Flux::toast(variant: 'success', text: __('Invitation sent.'));
    }

    public function resend(int $invitationId): void
    {
        $church = $this->church();

        $this->authorize('manageMembers', $church);

        $invitation = $church->invitations()->whereKey($invitationId)->firstOrFail();

        // Refresh the token and expiry, then re-send.
        $invitation = ChurchInvitation::createFor(
            $church,
            $invitation->email,
            $invitation->accessRoles(),
            Auth::user(),
        );

        Mail::to($invitation->email)->send(new ChurchInvitationMail($invitation));

        unset($this->invitations);

        Flux::toast(variant: 'success', text: __('Invitation re-sent.'));
    }

    public function revoke(int $invitationId): void
    {
        $church = $this->church();

        $this->authorize('manageMembers', $church);

        $church->invitations()->whereKey($invitationId)->delete();

        unset($this->invitations);

        Flux::toast(variant: 'success', text: __('Invitation revoked.'));
    }

    public function regenerateJoinLink(): void
    {
        $church = $this->church();

        $this->authorize('manageMembers', $church);

        $church->regenerateJoinToken();

        Flux::toast(variant: 'success', text: __('Join link regenerated. Previously shared links and QR codes no longer work.'));
    }

    public function disableJoinLink(): void
    {
        $church = $this->church();

        $this->authorize('manageMembers', $church);

        $church->disableJoinToken();

        Flux::toast(variant: 'success', text: __('Join link disabled.'));
    }

    /** @return Collection<int, ChurchInvitation> */
    #[Computed]
    public function invitations(): Collection
    {
        return $this->church()->invitations()
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function joinUrl(): ?string
    {
        $token = $this->church()->join_token;

        return $token !== null ? route('churches.join', $token) : null;
    }

    private function church(): Church
    {
        return Auth::user()->currentChurch;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Invitations')" :subheading="__('Invite people to join your church')">
        <form wire:submit="invite" class="my-6 w-full space-y-6">
            <flux:input wire:model="email" :label="__('Email address')" type="email" required placeholder="name@example.com" />

            <flux:checkbox.group wire:model="roles" :label="__('Access roles')" :description="__('Optional. Members without roles can still view groups and messages.')">
                @foreach (AccessRole::cases() as $role)
                    <flux:checkbox :value="$role->value" :label="$role->label()" :description="$role->description()" />
                @endforeach
            </flux:checkbox.group>

            <flux:button type="submit" variant="primary">{{ __('Send invitation') }}</flux:button>
        </form>

        <flux:separator class="my-8" />

        <flux:heading class="mb-4 font-semibold">{{ __('Pending invitations') }}</flux:heading>

        @if ($this->invitations->isEmpty())
            <flux:text>{{ __('No pending invitations.') }}</flux:text>
        @else
            <div class="space-y-3">
                @foreach ($this->invitations as $invitation)
                    <div wire:key="invitation-{{ $invitation->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">{{ $invitation->email }}</div>
                            <div class="text-xs text-zinc-500">
                                @if ($invitation->isExpired())
                                    {{ __('Expired :date', ['date' => $invitation->expires_at->toFormattedDateString()]) }}
                                @else
                                    {{ __('Expires :date', ['date' => $invitation->expires_at->toFormattedDateString()]) }}
                                @endif
                                @if ($invitation->accessRoles() !== [])
                                    · {{ collect($invitation->accessRoles())->map(fn ($role) => $role->shortLabel())->implode(', ') }}
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <flux:button size="sm" variant="subtle" wire:click="resend({{ $invitation->id }})">{{ __('Resend') }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="revoke({{ $invitation->id }})">{{ __('Revoke') }}</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <flux:separator class="my-8" />

        <flux:heading class="mb-1 font-semibold">{{ __('Join link') }}</flux:heading>
        <flux:text class="mb-4">{{ __('Anyone with this link (or QR code) can join your church as a member with no staff roles. Share it in a bulletin or post it in the foyer.') }}</flux:text>

        @if ($this->joinUrl)
            <div class="space-y-4">
                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <flux:input :value="$this->joinUrl" readonly class="flex-1" />
                    <flux:button
                        icon="clipboard"
                        x-on:click="navigator.clipboard?.writeText(@js($this->joinUrl)); copied = true; setTimeout(() => copied = false, 1600)"
                    >
                        <span x-show="! copied">{{ __('Copy') }}</span>
                        <span x-show="copied" style="display: none">{{ __('Copied!') }}</span>
                    </flux:button>
                </div>

                <div class="inline-block rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700">
                    {!! App\Support\QrCode::svg($this->joinUrl) !!}
                </div>

                <div class="flex items-center gap-2">
                    <flux:button
                        variant="subtle"
                        wire:click="regenerateJoinLink"
                        wire:confirm="{{ __('Regenerate the join link? Previously shared links and printed QR codes will stop working.') }}"
                    >{{ __('Regenerate link') }}</flux:button>

                    <flux:button
                        variant="danger"
                        wire:click="disableJoinLink"
                        wire:confirm="{{ __('Disable the join link? No one will be able to join with it until you create a new one.') }}"
                    >{{ __('Disable link') }}</flux:button>
                </div>
            </div>
        @else
            <flux:button variant="primary" wire:click="regenerateJoinLink">{{ __('Create join link') }}</flux:button>
        @endif
    </x-settings.layout>
</section>
