@php
    use App\Enums\NotificationEventType;
    use Illuminate\Support\Str;
@endphp

<x-mail::message>
# Your {{ $frequency->label() }} Stave digest

Hi {{ $user->name }},

You have {{ $items->count() }} {{ Str::plural('update', $items->count()) }} since the last digest.

@foreach ($items->groupBy(fn ($item) => $item->event_type->value) as $eventValue => $group)
## {{ NotificationEventType::from($eventValue)->label() }}

@foreach ($group as $item)
- **{{ $item->data['title'] ?? 'Update' }}** — {{ Str::limit($item->data['body'] ?? '', 160) }}<br>
[Open]({{ $item->data['url'] ?? url('/') }})
@endforeach

@endforeach

<x-mail::button :url="route('settings.notifications')">
Notification settings
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
