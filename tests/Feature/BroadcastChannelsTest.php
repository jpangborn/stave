<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can authenticate to their own private notification channel', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => "private-App.Models.User.{$user->id}",
    ]);

    $response->assertSuccessful();
});

test('user cannot authenticate to another users private notification channel', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $this->actingAs($intruder);

    $response = $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => "private-App.Models.User.{$owner->id}",
    ]);

    $response->assertForbidden();
});
