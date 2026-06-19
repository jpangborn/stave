<?php

use App\Enums\AccessRole;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function careUser(): User
{
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    return $user;
}

test('non pastoral-care users cannot access the page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('pastoral-care.index'))
        ->assertForbidden();
});

test('pastoral-care users can view the page', function (): void {
    $this->actingAs(careUser())
        ->get(route('pastoral-care.index'))
        ->assertOk()
        ->assertSee('Pastoral Care');
});

test('mine scope shows only the elder\'s assignees and excludes self', function (): void {
    $user = careUser();
    $me = $user->person;
    $assignee = Person::factory()->create(['pastoral_care_elder_id' => $me->id]);
    $unrelated = Person::factory()->create();

    $component = Livewire::actingAs($user)->test('pages::pastoral-care.index');

    $ids = $component->instance()->people->pluck('id');
    expect($ids)->toContain($assignee->id)
        ->not->toContain($unrelated->id)
        ->not->toContain($me->id);
});

test('all scope shows the whole congregation', function (): void {
    $user = careUser();
    $me = $user->person;
    $shepherd = Person::factory()->create();
    $someone = Person::factory()->create(['pastoral_care_elder_id' => $shepherd->id]);

    $component = Livewire::actingAs($user)
        ->test('pages::pastoral-care.index')
        ->set('scope', 'all');

    $ids = $component->instance()->people->pluck('id');
    expect($ids)->toContain($someone->id)->toContain($me->id);
});

test('the first stat card label switches with scope', function (): void {
    Livewire::actingAs(careUser())
        ->test('pages::pastoral-care.index')
        ->assertSee('Under your care')
        ->set('scope', 'all')
        ->assertSee('In the congregation');
});

test('stats count open requests and people awaiting baptism within the scope', function (): void {
    $user = careUser();
    $me = $user->person;
    $assignee = Person::factory()->create(['pastoral_care_elder_id' => $me->id, 'baptized' => false]);
    PrayerRequest::factory()->open()->for($assignee)->create();
    PrayerRequest::factory()->completed()->for($assignee)->create();

    $stats = Livewire::actingAs($user)->test('pages::pastoral-care.index')->instance()->stats;

    expect($stats['count'])->toBe(1)
        ->and($stats['openRequests'])->toBe(1)
        ->and($stats['awaitingBaptism'])->toBe(1);
});

test('the sidebar links to pastoral care only for pastoral-care users', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('people.index'))
        ->assertDontSee('Pastoral Care');

    $this->actingAs(careUser())
        ->get(route('people.index'))
        ->assertSee('Pastoral Care');
});
