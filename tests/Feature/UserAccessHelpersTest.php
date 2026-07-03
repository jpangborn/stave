<?php

use App\Enums\AccessRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('canAccessLiturgy cases', [
    'liturgy user' => [[AccessRole::LITURGY_USER], true],
    'liturgy admin' => [[AccessRole::LITURGY_ADMIN], true],
    'admin' => [[AccessRole::ADMIN], true],
    'no roles' => [[], false],
    'pastoral care user only' => [[AccessRole::PASTORAL_CARE_USER], false],
    'pastoral care admin only' => [[AccessRole::PASTORAL_CARE_ADMIN], false],
]);

test('canAccessLiturgy reflects the user\'s access roles', function (array $roles, bool $expected): void {
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->grantAccessRole($role);
    }

    expect($user->canAccessLiturgy())->toBe($expected);
})->with('canAccessLiturgy cases');

dataset('canManageLiturgy cases', [
    'liturgy admin' => [[AccessRole::LITURGY_ADMIN], true],
    'admin' => [[AccessRole::ADMIN], true],
    'liturgy user' => [[AccessRole::LITURGY_USER], false],
    'no roles' => [[], false],
    'pastoral care user only' => [[AccessRole::PASTORAL_CARE_USER], false],
    'pastoral care admin only' => [[AccessRole::PASTORAL_CARE_ADMIN], false],
]);

test('canManageLiturgy reflects the user\'s access roles', function (array $roles, bool $expected): void {
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->grantAccessRole($role);
    }

    expect($user->canManageLiturgy())->toBe($expected);
})->with('canManageLiturgy cases');
