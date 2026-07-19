<x-mail::message>
# You're invited to {{ $invitation->church->name }}

{{ $invitation->invitedBy?->name ?? 'A church administrator' }} has invited you to join
**{{ $invitation->church->name }}** on Stave, the worship service planning app.

<x-mail::button :url="$invitation->acceptUrl()">
Accept Invitation
</x-mail::button>

This invitation expires {{ $invitation->expires_at->toFormattedDateString() }}.
If you weren't expecting it, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
