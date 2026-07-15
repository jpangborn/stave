<?php

use App\Enums\AccessRole;
use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('roles are scoped to the church they were granted in', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $user = User::factory()->forChurch($a)->create();
    $user->churches()->attach($b);

    $user->grantAccessRole(AccessRole::ADMIN, $a);

    expect($user->hasAccessRole(AccessRole::ADMIN, $a))->toBeTrue()
        ->and($user->hasAccessRole(AccessRole::ADMIN, $b))->toBeFalse();

    $this->assertDatabaseHas('user_access_roles', [
        'user_id' => $user->id,
        'church_id' => $a->id,
        'role' => AccessRole::ADMIN->value,
    ]);
});

test('an admin of one church has no capabilities in another', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $user = User::factory()->forChurch($a)->create();
    $user->churches()->attach($b);
    $user->grantAccessRole(AccessRole::ADMIN, $a);

    $this->actingAs($user);
    expect($user->canManageLiturgy())->toBeTrue()
        ->and($user->canAccessPastoralCare())->toBeTrue();

    $user->switchChurch($b);
    $fresh = $user->fresh();
    $this->actingAs($fresh);

    expect($fresh->canManageLiturgy())->toBeFalse()
        ->and($fresh->canAccessPastoralCare())->toBeFalse();

    $this->actingAs($fresh)->get('/pastoral-care')->assertForbidden();
});

test('granting without explicit church defaults to the current church', function (): void {
    $a = Church::factory()->create();
    $user = User::factory()->forChurch($a)->create();

    $user->grantAccessRole(AccessRole::LITURGY_ADMIN);

    $this->assertDatabaseHas('user_access_roles', [
        'user_id' => $user->id,
        'church_id' => $a->id,
        'role' => AccessRole::LITURGY_ADMIN->value,
    ]);

    $user->revokeAccessRole(AccessRole::LITURGY_ADMIN);

    $this->assertDatabaseMissing('user_access_roles', [
        'user_id' => $user->id,
        'church_id' => $a->id,
        'role' => AccessRole::LITURGY_ADMIN->value,
    ]);
});

test('the prayer schedule digest command stays inside each church', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $userA = User::factory()->forChurch($a)->create();
    $userB = User::factory()->forChurch($b)->create();

    $this->artisan('stave:send-prayer-schedule')
        ->expectsOutputToContain($a->name)
        ->expectsOutputToContain($b->name)
        ->assertSuccessful();
});
