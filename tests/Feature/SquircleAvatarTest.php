<?php

use Illuminate\Support\Facades\Blade;

it('renders deterministic palette hue for given initials', function (): void {
    $first = Blade::render('<x-service.squircle-avatar name="Joshua Pangborn" :size="24" />');
    $second = Blade::render('<x-service.squircle-avatar name="Joshua Pangborn" :size="24" />');

    expect($first)->toBe($second);
});

it('uses one of the safelisted Flexoki hues', function (): void {
    $rendered = Blade::render('<x-service.squircle-avatar name="Derek Brown" :size="24" />');

    $hues = ['red', 'orange', 'yellow', 'green', 'cyan', 'blue', 'purple', 'pink'];
    $matched = collect($hues)->first(fn ($hue) => str_contains($rendered, "bg-{$hue}-100"));

    expect($matched)->not->toBeNull();
});

it('renders the first two initials of the name', function (): void {
    $rendered = Blade::render('<x-service.squircle-avatar name="Joseph Louthan" :size="24" />');

    expect($rendered)->toContain('JL');
});

it('renders a question mark when the name is empty', function (): void {
    $rendered = Blade::render('<x-service.squircle-avatar name="" :size="24" />');

    expect($rendered)->toContain('?');
});

it('renders an image when a src is provided', function (): void {
    $rendered = Blade::render('<x-service.squircle-avatar name="Test" :size="24" src="https://example.com/avatar.png" />');

    expect($rendered)
        ->toContain('https://example.com/avatar.png')
        ->toContain('<img');
});
