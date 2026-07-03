<?php

use App\Enums\AccessRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function (): void {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('the dashboard sets the browser title', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertSee('<title>Dashboard</title>', false);
});

test('greeting renders for an authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertSee('Good ');
    $response->assertSee($user->person->first_name);
});

dataset('quick action gating cases', [
    'no roles' => [[], ['New Conversation'], ['New Service', 'Add Song', 'Add Prayer Request', 'Add Pastoral Note']],
    'liturgy user' => [[AccessRole::LITURGY_USER], ['New Service', 'New Conversation'], ['Add Song', 'Add Prayer Request', 'Add Pastoral Note']],
    'liturgy admin' => [[AccessRole::LITURGY_ADMIN], ['New Service', 'Add Song', 'New Conversation'], ['Add Prayer Request', 'Add Pastoral Note']],
    'pastoral care user' => [[AccessRole::PASTORAL_CARE_USER], ['Add Prayer Request', 'Add Pastoral Note', 'New Conversation'], ['New Service', 'Add Song']],
    'admin' => [[AccessRole::ADMIN], ['New Service', 'Add Song', 'Add Prayer Request', 'Add Pastoral Note', 'New Conversation'], []],
]);

test('quick action buttons are gated by access role', function (array $roles, array $visible, array $hidden): void {
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->grantAccessRole($role);
    }

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    foreach ($visible as $label) {
        $response->assertSee($label);
    }

    foreach ($hidden as $label) {
        $response->assertDontSee($label);
    }
})->with('quick action gating cases');

test('new conversation link points to the compose form', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertSee('compose=1');
});

/* ---------------------------- widget heading matrix ---------------------------- */

dataset('widget heading gating cases', [
    'no roles' => [
        [],
        ['>Upcoming Services<', '>Group Messages<', '>Messages<', 'Quick actions'],
        ['My Assignments', 'Service Readiness', 'Rotation Candidates', 'Prayer This Week', 'Prayer Requests', 'Pastoral Notes', 'My Care List', 'Upcoming Birthdays'],
    ],
    'liturgy user' => [
        [AccessRole::LITURGY_USER],
        ['My Assignments'],
        ['Service Readiness', 'Rotation Candidates'],
    ],
    'liturgy admin' => [
        [AccessRole::LITURGY_ADMIN],
        ['My Assignments', 'Service Readiness', 'Rotation Candidates'],
        [],
    ],
    'pastoral care user' => [
        [AccessRole::PASTORAL_CARE_USER],
        ['Prayer This Week', 'Prayer Requests', 'Pastoral Notes', 'My Care List', 'Upcoming Birthdays'],
        ['My Assignments'],
    ],
    'admin' => [
        [AccessRole::ADMIN],
        ['My Assignments', 'Service Readiness', 'Rotation Candidates', 'Prayer This Week', 'Prayer Requests', 'Pastoral Notes', 'My Care List', 'Upcoming Birthdays'],
        [],
    ],
]);

test('widget headings are gated by access role on initial render', function (array $roles, array $visible, array $hidden): void {
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->grantAccessRole($role);
    }

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    foreach ($visible as $needle) {
        $response->assertSeeHtml($needle);
    }

    foreach ($hidden as $needle) {
        $response->assertDontSee($needle);
    }
})->with('widget heading gating cases');

/* ---------------------------- island security properties ---------------------------- */

test('a non-pastoral user loading my-care-list gets no island fragment', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('pages::dashboard.index')->loadIsland('my-care-list');

    $fragments = collect($component->effects['islandFragments'] ?? [])
        ->filter(fn ($fragment) => str_contains($fragment, 'name=my-care-list|'));

    expect($fragments)->toBeEmpty();
});

test('a non-pastoral user loading recent-prayer-requests gets no island fragment', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('pages::dashboard.index')->loadIsland('recent-prayer-requests');

    $fragments = collect($component->effects['islandFragments'] ?? [])
        ->filter(fn ($fragment) => str_contains($fragment, 'name=recent-prayer-requests|'));

    expect($fragments)->toBeEmpty();
});

test('a non-admin user loading service-readiness gets no island fragment', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::LITURGY_USER);

    $component = Livewire::actingAs($user)->test('pages::dashboard.index')->loadIsland('service-readiness');

    $fragments = collect($component->effects['islandFragments'] ?? [])
        ->filter(fn ($fragment) => str_contains($fragment, 'name=service-readiness|'));

    expect($fragments)->toBeEmpty();
});
