@php
    use Illuminate\Support\Str;
@endphp

<x-mail::message>
# Prayer for the week

Good morning, elders.

Here are the {{ count($people) }} {{ Str::plural('person', count($people)) }} to hold in prayer this week (Week {{ $weekNumber }} of {{ $totalWeeks }}, {{ $weekRange }}). Please pray for them and their households by name.

@foreach ($people as $person)
## {{ $person['name'] }}@if ($person['household']) · {{ $person['household'] }} household @endif

@if (count($person['requests']) > 0)
@foreach ($person['requests'] as $request)
- {{ $request }}
@endforeach
@else
_Pray for them and their household by name — no specific requests this week._
@endif

@endforeach
<x-mail::panel>
Private notes and requests marked confidential are never included in this email. Only requests shared for the bulletin appear above.
</x-mail::panel>

In Christ,<br>
The Stave prayer rota · {{ config('app.name') }}
</x-mail::message>
