<?php

use App\Models\Church;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('single-church users see their church without a switcher dropdown', function (): void {
    $church = Church::factory()->create(['name' => 'Solo Chapel']);
    $user = User::factory()->forChurch($church)->create();

    Livewire::actingAs($user)
        ->test('sidebar.church-switcher')
        ->assertSee('Solo Chapel')
        ->assertDontSee('chevrons-up-down');
});

test('multi-church users can switch churches', function (): void {
    $a = Church::factory()->create(['name' => 'Alpha Chapel']);
    $b = Church::factory()->create(['name' => 'Beta Chapel']);

    $user = User::factory()->forChurch($a)->create();
    $user->churches()->attach($b);

    Livewire::actingAs($user)
        ->test('sidebar.church-switcher')
        ->assertSee('Alpha Chapel')
        ->assertSee('Beta Chapel')
        ->call('switch', $b->id)
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->current_church_id)->toBe($b->id);
});

test('users cannot switch to a church they are not a member of', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $user = User::factory()->forChurch($a)->create();

    $component = Livewire::actingAs($user)->test('sidebar.church-switcher');

    expect(fn () => $component->call('switch', $b->id))
        ->toThrow(ModelNotFoundException::class);

    expect($user->fresh()->current_church_id)->toBe($a->id);
});

test('users without a church are redirected to onboarding', function (): void {
    $user = User::factory()->create();
    $user->churches()->detach();
    $user->forceFill(['current_church_id' => null])->save();

    $this->actingAs($user)
        ->get('/songs')
        ->assertRedirect(route('churches.create'));
});

test('a churchless user can create a church from onboarding', function (): void {
    $user = User::factory()->create();
    $user->churches()->detach();
    $user->forceFill(['current_church_id' => null])->save();

    Livewire::actingAs($user)
        ->test('pages::churches.create')
        ->set('name', 'Fresh Start Church')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $church = Church::query()->where('name', 'Fresh Start Church')->sole();

    expect($user->fresh()->current_church_id)->toBe($church->id)
        ->and($church->hasMember($user))->toBeTrue();
});

test('a stale current church self-heals to an existing membership', function (): void {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $user = User::factory()->forChurch($a)->create();
    $user->churches()->attach($b);

    // Simulate removal from the current church.
    $user->churches()->detach($a);

    $this->actingAs($user)->get('/songs')->assertOk();

    expect($user->fresh()->current_church_id)->toBe($b->id);
});
