<?php

use App\Enums\AccessRole;
use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function careListFor(User $user): Collection
{
    return Livewire::actingAs($user)->test('pages::dashboard.index')->instance()->careList;
}

/** A PASTORAL_CARE_USER whose User is linked to the given elder Person. */
function elderUser(Person $elder): User
{
    $user = User::factory()->create(['person_id' => $elder->id]);
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    return $user;
}

test('careList orders never-noted congregants first, then oldest note, then most recent note', function (): void {
    $elder = Person::factory()->create();
    $user = elderUser($elder);

    $neverNoted = Person::factory()->create(['pastoral_care_elder_id' => $elder->id, 'last_name' => 'Zed']);

    $oldNoted = Person::factory()->create(['pastoral_care_elder_id' => $elder->id, 'last_name' => 'Old']);
    PastoralNote::factory()->for($oldNoted, 'person')->create(['created_at' => now()->subWeeks(10)]);

    $recentNoted = Person::factory()->create(['pastoral_care_elder_id' => $elder->id, 'last_name' => 'Recent']);
    PastoralNote::factory()->for($recentNoted, 'person')->create(['created_at' => now()->subDay()]);

    $ids = careListFor($user)->pluck('id')->all();

    expect($ids)->toBe([$neverNoted->id, $oldNoted->id, $recentNoted->id]);
});

test('careList only counts the latest note per person', function (): void {
    $elder = Person::factory()->create();
    $user = elderUser($elder);

    $congregant = Person::factory()->create(['pastoral_care_elder_id' => $elder->id]);
    PastoralNote::factory()->for($congregant, 'person')->create(['created_at' => now()->subWeeks(10)]);
    PastoralNote::factory()->for($congregant, 'person')->create(['created_at' => now()->subDay()]);

    $lastNotedAt = careListFor($user)->firstWhere('id', $congregant->id)->last_noted_at;

    expect($lastNotedAt->isSameDay(now()->subDay()))->toBeTrue();
});

test('careList excludes people assigned to a different elder', function (): void {
    $elder = Person::factory()->create();
    $user = elderUser($elder);

    $otherElder = Person::factory()->create();
    $notMine = Person::factory()->create(['pastoral_care_elder_id' => $otherElder->id]);

    expect(careListFor($user)->pluck('id')->all())->not->toContain($notMine->id);
});

test('careList is limited to 5 people', function (): void {
    $elder = Person::factory()->create();
    $user = elderUser($elder);

    Person::factory()->count(6)->create(['pastoral_care_elder_id' => $elder->id]);

    expect(careListFor($user))->toHaveCount(5);
});

test('careList is empty when the user has no linked person, and the dashboard still renders', function (): void {
    // `users.person_id` is a NOT NULL foreign key in this schema, so a User can
    // never be persisted without a Person. We simulate the defensive branch by
    // overriding the loaded relation on the acting user (which `auth()->user()`
    // resolves to for the duration of this in-process test).
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);
    $user->setRelation('person', null);

    expect(careListFor($user))->toBeEmpty();

    $this->actingAs($user);
    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('an access-role-gated user without pastoral access does not see pastoral islands', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertDontSee('My Care List');
});

test('a pastoral care user sees the My Care List heading', function (): void {
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('My Care List');
});
