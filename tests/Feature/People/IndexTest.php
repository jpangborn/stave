<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
    Person::query()->delete();
});

test('renders the people index', function (): void {
    Person::factory()->count(3)->create();

    $this->get('/people')->assertOk();
});

test('shows empty state when no people exist', function (): void {
    $this->get('/people')->assertOk()->assertSee('No people yet');
});

test('counts each membership status', function (): void {
    Person::factory()->member()->count(3)->create();
    Person::factory()->catechumen()->count(2)->create();
    Person::factory()->adherent()->count(1)->create();
    Person::factory()->visitor()->count(4)->create();

    Livewire::test('pages::people.index')
        ->assertSet('filter', null)
        ->tap(function ($t): void {
            $counts = $t->instance()->counts;
            expect($counts)->toMatchArray([
                'all' => 10,
                'member' => 3,
                'catechumen' => 2,
                'adherent' => 1,
                'visitor' => 4,
            ]);
        });
});

test('filters by membership status', function (): void {
    Person::factory()->member()->count(2)->create();
    Person::factory()->visitor()->count(3)->create();

    Livewire::test('pages::people.index')
        ->call('setFilter', 'member')
        ->assertSet('filter', 'member')
        ->tap(function ($t): void {
            expect($t->instance()->people->total())->toBe(2);
        });
});

test('setFilter(all) clears the filter', function (): void {
    Person::factory()->visitor()->create();

    Livewire::test('pages::people.index')
        ->call('setFilter', 'member')
        ->call('setFilter', 'all')
        ->assertSet('filter', null);
});

test('searches by name', function (): void {
    Person::factory()->create(['first_name' => 'Joshua', 'last_name' => 'Pangborn']);
    Person::factory()->create(['first_name' => 'Andrew', 'last_name' => 'Burnette']);

    Livewire::test('pages::people.index')
        ->set('search', 'Pangborn')
        ->tap(function ($t): void {
            expect($t->instance()->people->total())->toBe(1);
        });
});

test('openPerson dispatches drawer event', function (): void {
    $person = Person::factory()->create();

    Livewire::test('pages::people.index')
        ->call('openPerson', $person->id)
        ->assertSet('openPersonId', $person->id)
        ->assertDispatched('open-person-drawer');
});
