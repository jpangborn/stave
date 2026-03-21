<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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

test('leaders can delete a group from the index page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['name' => 'Old Group', 'visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.index')
        ->assertSee('Old Group')
        ->call('delete', $group->id);

    $this->assertDatabaseMissing('groups', ['id' => $group->id]);
});

test('non-leaders cannot delete a group from the index page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['visibility' => GroupVisibility::PUBLIC]);

    $this->actingAs($user);

    Livewire::test('pages::groups.index')
        ->call('delete', $group->id)
        ->assertForbidden();

    $this->assertDatabaseHas('groups', ['id' => $group->id]);
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

test('group creator is automatically assigned as leader', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::groups.create')
        ->set('form.name', 'New Group')
        ->call('save')
        ->assertHasNoErrors();

    $group = Group::where('name', 'New Group')->first();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupRole::LEADER->value,
        'status' => MembershipStatus::ACTIVE->value,
    ]);
});

test('members relationship returns only active users', function (): void {
    $group = Group::factory()->create();
    $active = User::factory()->create();
    $pending = User::factory()->create();

    $group->allUsers()->attach($active, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($pending, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    expect($group->members)->toHaveCount(1);
    expect($group->members->first()->id)->toBe($active->id);
});

test('leaders relationship returns only active leaders', function (): void {
    $group = Group::factory()->create();
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $pendingLeader = User::factory()->create();

    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($pendingLeader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::PENDING]);

    expect($group->leaders)->toHaveCount(1);
    expect($group->leaders->first()->id)->toBe($leader->id);
});

test('pending requests returns only pending users', function (): void {
    $group = Group::factory()->create();
    $pending = User::factory()->create();
    $active = User::factory()->create();

    $group->allUsers()->attach($pending, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);
    $group->allUsers()->attach($active, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    expect($group->pendingRequests)->toHaveCount(1);
    expect($group->pendingRequests->first()->id)->toBe($pending->id);
});

test('all users returns every membership regardless of status', function (): void {
    $group = Group::factory()->create();
    $users = User::factory()->count(3)->create();

    $group->allUsers()->attach($users[0], ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($users[1], ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);
    $group->allUsers()->attach($users[2], ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::REJECTED]);

    expect($group->allUsers)->toHaveCount(3);
});

test('user groups relationship returns only active memberships', function (): void {
    $user = User::factory()->create();
    $activeGroup = Group::factory()->create();
    $pendingGroup = Group::factory()->create();

    $activeGroup->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $pendingGroup->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::PENDING]);

    expect($user->groups)->toHaveCount(1);
    expect($user->groups->first()->id)->toBe($activeGroup->id);
});

test('duplicate group membership is prevented', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    expect(fn () => $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('deleting a group removes its memberships', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $group->delete();

    $this->assertDatabaseMissing('group_user', ['group_id' => $group->id]);
});

/** @group groups-edit */
test('guests are redirected from the groups edit page', function (): void {
    $group = Group::factory()->create();

    $this->get(route('groups.edit', $group))
        ->assertRedirect('/login');
});

test('leaders can view the groups edit page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user)
        ->get(route('groups.edit', $group))
        ->assertStatus(200)
        ->assertSee('Edit Group');
});

test('non-leaders cannot view the groups edit page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user)
        ->get(route('groups.edit', $group))
        ->assertStatus(403);
});

test('leaders can update a group', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['name' => 'Old Name', 'visibility' => GroupVisibility::PUBLIC]);
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.edit', ['group' => $group])
        ->set('form.name', 'New Name')
        ->set('form.description', '<p>Updated description</p>')
        ->set('form.visibility', GroupVisibility::PRIVATE->value)
        ->set('form.messaging', GroupMessaging::ALL_MEMBERS->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseHas('groups', [
        'id' => $group->id,
        'name' => 'New Name',
        'visibility' => 'private',
        'messaging' => 'all-members',
    ]);
});

test('leaders can update a group with a new image', function (): void {
    Storage::fake('digital-ocean');

    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    $image = UploadedFile::fake()->image('new-photo.jpg');

    Livewire::test('pages::groups.edit', ['group' => $group])
        ->set('image', $image)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('groups.show', $group));

    $group->refresh();
    expect($group->image)->not->toBeNull();

    Storage::disk('digital-ocean')->assertExists($group->image);
});

test('leaders can remove an existing group image', function (): void {
    Storage::fake('digital-ocean');
    Storage::disk('digital-ocean')->put('groups/old-image.jpg', 'fake-image-data');

    $user = User::factory()->create();
    $group = Group::factory()->create(['image' => 'groups/old-image.jpg']);
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.edit', ['group' => $group])
        ->call('removeImage')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('groups.show', $group));

    $group->refresh();
    expect($group->image)->toBeNull();

    Storage::disk('digital-ocean')->assertMissing('groups/old-image.jpg');
});

test('leaders can delete a group from the edit page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->allUsers()->attach($user, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $this->actingAs($user);

    Livewire::test('pages::groups.edit', ['group' => $group])
        ->call('delete')
        ->assertRedirect(route('groups.index'));

    $this->assertDatabaseMissing('groups', ['id' => $group->id]);
});
