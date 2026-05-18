<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

it('returns a gravatar URL when one exists for the user', function (): void {
    Http::fake([
        'gravatar.com/*' => Http::response('', 200),
    ]);

    $user = User::factory()->create(['email' => 'has-photo@example.com']);

    expect($user->gravatarUrl())
        ->toBeString()
        ->toContain('gravatar.com/avatar/')
        ->toContain('s=96');
});

it('returns null when the user has no gravatar registered', function (): void {
    Http::fake([
        'gravatar.com/*' => Http::response('', 404),
    ]);

    $user = User::factory()->create(['email' => 'no-photo@example.com']);

    expect($user->gravatarUrl())->toBeNull();
});

it('caches the gravatar existence check', function (): void {
    Http::fake([
        'gravatar.com/*' => Http::response('', 404),
    ]);

    $user = User::factory()->create(['email' => 'cached@example.com']);
    $user->gravatarUrl();
    $user->gravatarUrl();

    Http::assertSentCount(1);
});

it('returns null when the user has no email', function (): void {
    Http::fake();

    $user = User::factory()->make(['email' => '']);

    expect($user->gravatarUrl())->toBeNull();
    Http::assertNothingSent();
});

it('falls back to null when the network request throws', function (): void {
    Http::fake(function (Request $request) {
        throw new RuntimeException('Network down');
    });

    $user = User::factory()->create(['email' => 'flaky@example.com']);

    expect($user->gravatarUrl())->toBeNull();
});
