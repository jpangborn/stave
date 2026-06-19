<?php

use App\Enums\AccessRole;
use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function userWithRole(?AccessRole $role): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->grantAccessRole($role);
    }

    return $user;
}

test('a pastoral note belongs to a person and an author', function (): void {
    $person = Person::factory()->create();
    $author = User::factory()->create();
    $note = PastoralNote::factory()->for($person)->state(['author_id' => $author->id])->create();

    expect($note->person->is($person))->toBeTrue()
        ->and($note->author->is($author))->toBeTrue();
});

test('a person has many pastoral notes', function (): void {
    $person = Person::factory()->create();
    PastoralNote::factory()->count(2)->for($person)->create();
    PastoralNote::factory()->create();

    expect($person->pastoralNotes)->toHaveCount(2);
});

test('pastoral-care users can view and create notes', function (): void {
    $user = userWithRole(AccessRole::PASTORAL_CARE_USER);
    $note = PastoralNote::factory()->create();

    expect($user->can('viewAny', PastoralNote::class))->toBeTrue()
        ->and($user->can('view', $note))->toBeTrue()
        ->and($user->can('create', PastoralNote::class))->toBeTrue();
});

test('users without pastoral-care access cannot view or create notes', function (): void {
    $user = userWithRole(AccessRole::LITURGY_USER);
    $note = PastoralNote::factory()->create();

    expect($user->can('viewAny', PastoralNote::class))->toBeFalse()
        ->and($user->can('view', $note))->toBeFalse()
        ->and($user->can('create', PastoralNote::class))->toBeFalse();
});

test('admins can access notes through the admin role', function (): void {
    $user = userWithRole(AccessRole::ADMIN);
    $note = PastoralNote::factory()->create();

    expect($user->can('view', $note))->toBeTrue()
        ->and($user->can('create', PastoralNote::class))->toBeTrue();
});

test('only the author or an admin can update or delete a note', function (): void {
    $author = userWithRole(AccessRole::PASTORAL_CARE_USER);
    $otherCareUser = userWithRole(AccessRole::PASTORAL_CARE_USER);
    $careAdmin = userWithRole(AccessRole::PASTORAL_CARE_ADMIN);

    $note = PastoralNote::factory()->state(['author_id' => $author->id])->create();

    expect($author->can('update', $note))->toBeTrue()
        ->and($author->can('delete', $note))->toBeTrue()
        ->and($otherCareUser->can('update', $note))->toBeFalse()
        ->and($otherCareUser->can('delete', $note))->toBeFalse()
        ->and($careAdmin->can('update', $note))->toBeTrue()
        ->and($careAdmin->can('delete', $note))->toBeTrue();
});
