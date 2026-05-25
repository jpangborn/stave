<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @group browser */
it('renders the empty state when there are no people', function (): void {
    $user = User::factory()->create();
    $user->person->delete();
    Person::query()->delete();

    $this->actingAs($user);

    visit(route('people.index'))
        ->assertSee('No people yet')
        ->assertSee('Add your first person')
        ->assertNoSmoke();
});

/** @group browser */
it('filters by membership status', function (): void {
    $user = User::factory()->create();
    $user->person->delete();
    Person::query()->delete();

    Person::factory()->member()->count(3)->create();
    Person::factory()->visitor()->count(2)->create();

    $this->actingAs($user);

    visit(route('people.index'))
        ->assertSee('Members')
        ->assertSee('Visitors')
        ->assertNoSmoke();
});
