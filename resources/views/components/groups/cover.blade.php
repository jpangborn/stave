@props([
    'group',
    'height' => '88px',
    'rounded' => 'none',
    'initialSize' => '36%',
])

@php
    $roundedClass = match ($rounded) {
        'none' => '',
        'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        'lg' => 'rounded-lg',
        default => $rounded,
    };
@endphp

<div
    class="relative w-full overflow-hidden {{ $roundedClass }}"
    style="height: {{ $height }};"
>
    @if ($group->cover_url)
        <img
            src="{{ $group->cover_url }}"
            alt=""
            class="h-full w-full object-cover"
            loading="lazy"
        />
    @else
        <div
            class="flex h-full w-full items-center justify-center bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-600 font-extrabold leading-none select-none"
            style="
                font-size: {{ $initialSize }};
                background-image: repeating-linear-gradient(
                    45deg,
                    transparent 0,
                    transparent 6px,
                    rgb(0 0 0 / 0.04) 6px,
                    rgb(0 0 0 / 0.04) 7px
                );
            "
            aria-hidden="true"
        >{{ $group->first_letter }}</div>
    @endif

    {{ $slot }}
</div>
