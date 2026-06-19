<?php

use App\Enums\AccessRole;
use App\Enums\PrayerRequestVisibility;
use App\Models\Group;
use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function actingCareUser(): User
{
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    return $user;
}

test('renders the person page with profile and cards', function (): void {
    $person = Person::factory()->create(['first_name' => 'Mark', 'last_name' => 'Aldridge']);

    $this->actingAs(User::factory()->create())
        ->get(route('people.show', $person))
        ->assertOk()
        ->assertSee('Mark Aldridge')
        ->assertSee('Profile')
        ->assertSee('Prayer Requests');
});

test('the edit profile button is wired to open the drawer', function (): void {
    $person = Person::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('people.show', $person))
        ->assertSeeHtml('open-person-drawer');
});

test('adds a prayer request attributed to the current user', function (): void {
    $user = User::factory()->create();
    $person = Person::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::people.show', ['person' => $person])
        ->set('newRequestBody', 'Pray for safe travels')
        ->set('newRequestVisibility', 'private')
        ->call('addPrayerRequest')
        ->assertHasNoErrors();

    $request = PrayerRequest::query()->first();
    expect($request->body)->toBe('Pray for safe travels')
        ->and($request->visibility)->toBe(PrayerRequestVisibility::PRIVATE)
        ->and($request->created_by_user_id)->toBe($user->id)
        ->and($request->person_id)->toBe($person->id);
});

test('show completed toggle hides old completed requests', function (): void {
    $person = Person::factory()->create();
    PrayerRequest::factory()->open()->for($person)->create();
    PrayerRequest::factory()->for($person)->state(['completed_at' => now()->subDays(2)])->create();
    PrayerRequest::factory()->for($person)->state(['completed_at' => now()->subDays(30)])->create();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::people.show', ['person' => $person]);

    expect($component->instance()->prayerRequests)->toHaveCount(2)
        ->and($component->instance()->hiddenCompletedCount)->toBe(1);

    $component->set('showCompleted', true);

    expect($component->instance()->prayerRequests)->toHaveCount(3)
        ->and($component->instance()->hiddenCompletedCount)->toBe(0);
});

test('toggling a request completes and reopens it', function (): void {
    $person = Person::factory()->create();
    $request = PrayerRequest::factory()->open()->for($person)->create();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::people.show', ['person' => $person])
        ->call('toggleComplete', $request->id);

    expect($request->refresh()->completed_at)->not->toBeNull();

    $component->call('toggleComplete', $request->id);

    expect($request->refresh()->completed_at)->toBeNull();
});

test('pastoral notes are hidden from users without pastoral-care access', function (): void {
    $person = Person::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('people.show', $person))
        ->assertDontSee('Pastoral Notes');
});

test('pastoral-care users can add a note', function (): void {
    $user = actingCareUser();
    $person = Person::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::people.show', ['person' => $person])
        ->set('newNote', 'Met for coffee — encouraged.')
        ->call('addNote')
        ->assertHasNoErrors();

    $note = PastoralNote::query()->first();
    expect($note->body)->toBe('Met for coffee — encouraged.')
        ->and($note->author_id)->toBe($user->id)
        ->and($note->person_id)->toBe($person->id);
});

test('adding a note is forbidden without pastoral-care access', function (): void {
    $person = Person::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::people.show', ['person' => $person])
        ->set('newNote', 'Should not save')
        ->call('addNote')
        ->assertForbidden();

    expect(PastoralNote::count())->toBe(0);
});

test('the messages panel is hidden when the person has no user account', function (): void {
    $person = Person::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('people.show', $person))
        ->assertDontSee('Direct conversation with');
});

test('the messages panel shows when the person has a user account', function (): void {
    $other = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('people.show', $other->person))
        ->assertSee('Direct conversation with');
});

test('sending a message creates the direct conversation', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    // Merely rendering the panel must not create a conversation.
    Livewire::actingAs($me)->test('people.messages-panel', ['person' => $other->person]);
    expect(Group::query()->direct()->count())->toBe(0);

    Livewire::actingAs($me)
        ->test('people.messages-panel', ['person' => $other->person])
        ->set('reply', '<p>Praying for you this week.</p>')
        ->call('send')
        ->assertHasNoErrors();

    $key = Group::directKeyFor([$me->id, $other->id]);
    $group = Group::query()->direct()->where('direct_key', $key)->first();

    expect($group)->not->toBeNull()
        ->and($group->directConversation->comments()->count())->toBe(1);
});
