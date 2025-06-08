{{-- Credit: Lucide (https://lucide.dev) --}}

@props([
    'variant' => 'outline',
])

@php
if ($variant === 'solid') {
    throw new \Exception('The "solid" variant is not supported in Lucide.');
}

$classes = Flux::classes('shrink-0')
    ->add(match($variant) {
        'outline' => '[:where(&)]:size-6',
        'solid' => '[:where(&)]:size-6',
        'mini' => '[:where(&)]:size-5',
        'micro' => '[:where(&)]:size-4',
    });

$strokeWidth = match ($variant) {
    'outline' => 2,
    'mini' => 2.25,
    'micro' => 2.5,
};
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    data-slot="icon"
>
  <path d="M13.5 3.1c-.5 0-1-.1-1.5-.1s-1 .1-1.5.1" />
  <path d="M19.3 6.8a10.45 10.45 0 0 0-2.1-2.1" />
  <path d="M20.9 13.5c.1-.5.1-1 .1-1.5s-.1-1-.1-1.5" />
  <path d="M17.2 19.3a10.45 10.45 0 0 0 2.1-2.1" />
  <path d="M10.5 20.9c.5.1 1 .1 1.5.1s1-.1 1.5-.1" />
  <path d="M3.5 17.5 2 22l4.5-1.5" />
  <path d="M3.1 10.5c0 .5-.1 1-.1 1.5s.1 1 .1 1.5" />
  <path d="M6.8 4.7a10.45 10.45 0 0 0-2.1 2.1" />
</svg>
