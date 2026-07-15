<?php

use App\Enums\AccessRole;
use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('non-admins cannot change access roles from the people drawer', function (): void {
    $church = Church::factory()->create();
    $editor = User::factory()->forChurch($church)->create();
    $target = User::factory()->forChurch($church)->create();

    Livewire::actingAs($editor)
        ->test('people.drawer')
        ->call('openPerson', $target->person->id)
        ->set('accessRoles.'.AccessRole::LITURGY_ADMIN->value, true)
        ->call('save')
        ->assertForbidden();

    expect($target->fresh()->hasAccessRole(AccessRole::LITURGY_ADMIN, $church))->toBeFalse();
});

test('admins can change access roles from the people drawer', function (): void {
    $church = Church::factory()->create();
    $editor = User::factory()->forChurch($church)->create();
    $editor->grantAccessRole(AccessRole::ADMIN, $church);
    $target = User::factory()->forChurch($church)->create();

    Livewire::actingAs($editor)
        ->test('people.drawer')
        ->call('openPerson', $target->person->id)
        ->set('accessRoles.'.AccessRole::LITURGY_ADMIN->value, true)
        ->call('save');

    expect($target->fresh()->hasAccessRole(AccessRole::LITURGY_ADMIN, $church))->toBeTrue();
});

test('the last admin of a church cannot be demoted', function (): void {
    $church = Church::factory()->create();
    $admin = User::factory()->forChurch($church)->create();
    $admin->grantAccessRole(AccessRole::ADMIN, $church);

    Livewire::actingAs($admin)
        ->test('people.drawer')
        ->call('openPerson', $admin->person->id)
        ->set('accessRoles.'.AccessRole::ADMIN->value, false)
        ->call('save');

    expect($admin->fresh()->hasAccessRole(AccessRole::ADMIN, $church))->toBeTrue();
});
