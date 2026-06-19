<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

test('the people list exposes a pastoral-care action linking to the person page', function (): void {
    $person = Person::factory()->create();

    $this->get('/people')
        ->assertOk()
        ->assertSeeHtml(route('people.show', $person))
        ->assertSeeHtml('Open pastoral care');
});
