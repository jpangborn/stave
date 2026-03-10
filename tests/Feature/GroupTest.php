<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupVisibility;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group groups */
test('guests are redirected from the groups index', function (): void {
    $response = $this->get('/groups');
    $response->assertRedirect('/login');
});

test('authenticated users can view the groups index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/groups')
        ->assertStatus(200);
});

test('guests are redirected from the groups create page', function (): void {
    $response = $this->get('/groups/create');
    $response->assertRedirect('/login');
});

test('authenticated users can view the groups create page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/groups/create')
        ->assertStatus(200)
        ->assertSee('Create a Group');
});

test('group name is required when creating a group', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::groups.create')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

test('authenticated users can create a group', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::groups.create')
        ->set('form.name', 'Youth Group')
        ->set('form.description', '<p>A group for youth</p>')
        ->set('form.visibility', GroupVisibility::PUBLIC->value)
        ->set('form.messaging', GroupMessaging::ALL_MEMBERS->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/groups');

    $this->assertDatabaseHas('groups', [
        'name' => 'Youth Group',
        'visibility' => 'public',
        'messaging' => 'all-members',
    ]);
});

test('authenticated users can create a group with image upload', function (): void {
    Storage::fake('digital-ocean');

    $user = User::factory()->create();
    $this->actingAs($user);

    $image = UploadedFile::fake()->image('group-photo.jpg');

    Livewire::test('pages::groups.create')
        ->set('form.name', 'Photo Group')
        ->set('image', $image)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/groups');

    $group = Group::where('name', 'Photo Group')->first();
    expect($group)->not->toBeNull();
    expect($group->image)->not->toBeNull();

    Storage::disk('digital-ocean')->assertExists($group->image);
});

test('image upload rejects non-image files', function (): void {
    Storage::fake('digital-ocean');

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test('pages::groups.create')
        ->set('form.name', 'Bad Upload Group')
        ->set('image', $file)
        ->call('save')
        ->assertHasErrors('image');
});

test('groups index displays public groups', function (): void {
    $user = User::factory()->create();
    Group::factory()->create(['name' => 'Public Worship', 'visibility' => GroupVisibility::PUBLIC]);
    Group::factory()->create(['name' => 'Secret Leaders', 'visibility' => GroupVisibility::PRIVATE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.index')
        ->assertSee('Public Worship')
        ->assertDontSee('Secret Leaders');
});

test('groups index search filters by name', function (): void {
    $user = User::factory()->create();
    Group::factory()->create(['name' => 'Youth Group', 'visibility' => GroupVisibility::PUBLIC]);
    Group::factory()->create(['name' => 'Choir', 'visibility' => GroupVisibility::PUBLIC]);

    $this->actingAs($user);

    $component = Livewire::test('pages::groups.index')
        ->set('search', 'Youth');

    $groups = $component->get('groups');
    expect($groups)->toHaveCount(1);
    expect($groups->first()->name)->toBe('Youth Group');
});

test('groups index can delete a group', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['name' => 'Old Group', 'visibility' => GroupVisibility::PUBLIC]);

    $this->actingAs($user);

    Livewire::test('pages::groups.index')
        ->assertSee('Old Group')
        ->call('delete', $group->id);

    $this->assertDatabaseMissing('groups', ['id' => $group->id]);
});

test('group defaults to public visibility and messaging off', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::groups.create')
        ->set('form.name', 'Default Group')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/groups');

    $group = Group::where('name', 'Default Group')->first();
    expect($group->visibility)->toBe(GroupVisibility::PUBLIC);
    expect($group->messaging)->toBe(GroupMessaging::OFF);
});
