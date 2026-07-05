{{-- Tiered stave-church mark: stacked gables + spire + cross. Stroke-based outline.
     Each path sets fill="none" explicitly so callers applying a `fill-current` class
     (which sets fill on the <svg>) don't fill the open paths. Defaults are provided via
     merge() so callers can override stroke-width / size / opacity per placement. --}}
<svg
    {{ $attributes->merge([
        'viewBox' => '0 0 200 240',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '9',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
    ]) }}
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <path fill="none" d="M58 214 V150 H142 V214" />
    <path fill="none" d="M38 150 L100 94 L162 150" />
    <path fill="none" d="M56 126 L100 78 L144 126" />
    <path fill="none" d="M72 106 L100 60 L128 106" />
    <path fill="none" d="M85 84 L100 44 L115 84" />
    <path fill="none" d="M94 60 L100 26 L106 60" />
    <path fill="none" d="M100 18 V32 M93 25 H107" />
</svg>
