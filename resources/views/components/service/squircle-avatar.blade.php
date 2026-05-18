@props([
    'name' => '',
    'size' => 24,
    'src' => null,
])

@php
    use App\Support\SectionTone;

    $initials = collect(preg_split('/\s+/', trim($name ?? '')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    if ($initials === '') {
        $initials = '?';
    }

    $radius = max(5, (int) round($size * 0.26));
    $fontSize = max(9, (int) round($size * 0.40));

    $first = mb_ord($initials[0] ?? '?');
    $second = mb_ord(mb_substr($initials, 1, 1) ?: '0');
    $index = ($first * 7 + $second) % count(SectionTone::PALETTE);
    $hue = SectionTone::PALETTE[$index];
@endphp

@if ($src)
    <img src="{{ $src }}"
         alt="{{ $name }}"
         style="width: {{ $size }}px; height: {{ $size }}px; border-radius: {{ $radius }}px;"
         class="inline-block shrink-0 object-cover" />
@else
    <span style="width: {{ $size }}px; height: {{ $size }}px; border-radius: {{ $radius }}px; font-size: {{ $fontSize }}px;"
          class="inline-flex shrink-0 items-center justify-center font-bold leading-none bg-{{ $hue }}-100 text-{{ $hue }}-700 dark:bg-{{ $hue }}-900 dark:text-{{ $hue }}-300">
        {{ $initials }}
    </span>
@endif
