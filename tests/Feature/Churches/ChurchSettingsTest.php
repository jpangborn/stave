<?php

use App\Enums\AccessRole;
use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected from church settings', function (): void {
    $this->get('/church/settings')->assertRedirect('/login');
});

test('only church admins can view church settings', function (?AccessRole $role, bool $allowed): void {
    $church = Church::factory()->create();
    $user = User::factory()->forChurch($church)->create();

    if ($role instanceof AccessRole) {
        $user->grantAccessRole($role, $church);
    }

    $this->actingAs($user)
        ->get('/church/settings')
        ->assertStatus($allowed ? 200 : 403);
})->with([
    'no roles' => [null, false],
    'liturgy user' => [AccessRole::LITURGY_USER, false],
    'liturgy admin' => [AccessRole::LITURGY_ADMIN, false],
    'pastoral care admin' => [AccessRole::PASTORAL_CARE_ADMIN, false],
    'admin' => [AccessRole::ADMIN, true],
]);

test('an admin can update the church profile', function (): void {
    $church = Church::factory()->create();
    $user = User::factory()->forChurch($church)->create();
    $user->grantAccessRole(AccessRole::ADMIN, $church);

    Livewire::actingAs($user)
        ->test('pages::church.settings')
        ->set('name', 'Renamed Church')
        ->set('timezone', 'America/Chicago')
        ->set('email', 'office@renamed.org')
        ->set('phone', '555-0100')
        ->set('address', '1 Main Street')
        ->set('website', 'https://renamed.org')
        ->call('updateChurch')
        ->assertHasNoErrors();

    $church->refresh();

    expect($church->name)->toBe('Renamed Church')
        ->and($church->timezone)->toBe('America/Chicago')
        ->and($church->email)->toBe('office@renamed.org')
        ->and($church->phone)->toBe('555-0100')
        ->and($church->address)->toBe('1 Main Street')
        ->and($church->website)->toBe('https://renamed.org');
});

test('an admin can upload and remove a church logo', function (): void {
    Storage::fake('digital-ocean');

    $church = Church::factory()->create();
    $user = User::factory()->forChurch($church)->create();
    $user->grantAccessRole(AccessRole::ADMIN, $church);

    Livewire::actingAs($user)
        ->test('pages::church.settings')
        ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
        ->call('updateChurch')
        ->assertHasNoErrors();

    $church->refresh();
    expect($church->logo_path)->not->toBeNull();
    Storage::disk('digital-ocean')->assertExists($church->logo_path);

    $path = $church->logo_path;

    Livewire::actingAs($user)
        ->test('pages::church.settings')
        ->call('removeLogo');

    expect($church->fresh()->logo_path)->toBeNull();
    Storage::disk('digital-ocean')->assertMissing($path);
});

test('an admin of another church cannot edit this church', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $user = User::factory()->forChurch($b)->create();
    $user->churches()->attach($a);
    $user->grantAccessRole(AccessRole::ADMIN, $a);

    // Current church is B, where the user holds no roles.
    $this->actingAs($user)->get('/church/settings')->assertForbidden();
});
