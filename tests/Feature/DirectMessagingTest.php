<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\User;
use App\Notifications\DirectMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/* --------------------------- page / access --------------------------- */

test('the messages index renders for an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertSee('Messages');
});

test('a non-participant cannot deep-link into a direct conversation', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();
    $stranger = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;

    $this->actingAs($stranger)
        ->get(route('messages.show', $conversation))
        ->assertNotFound();

    $this->actingAs($me)
        ->get(route('messages.show', $conversation))
        ->assertOk();
});

/* ------------------------------ compose ------------------------------ */

test('composing a one-to-one message creates a direct group, conversation, and comment', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create(['name' => 'Bruce Green']);

    Livewire::actingAs($me)
        ->test('pages::messages.index')
        ->call('newMessage')
        ->call('addRecipient', $other->id)
        ->set('reply', '<p>Hello Bruce</p>')
        ->call('sendCompose')
        ->assertHasNoErrors();

    $group = Group::query()->direct()->sole();

    expect($group->is_direct)->toBeTrue()
        ->and($group->direct_key)->toBe(Group::directKeyFor([$me->id, $other->id]))
        ->and($group->directConversation->comments()->count())->toBe(1);
});

test('messaging the same person again reuses the existing thread (no duplicate)', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $component = Livewire::actingAs($me)->test('pages::messages.index');

    $component->call('newMessage')->call('addRecipient', $other->id)
        ->set('reply', '<p>first</p>')->call('sendCompose')->assertHasNoErrors();

    $component->call('newMessage')->call('addRecipient', $other->id)
        ->set('reply', '<p>second</p>')->call('sendCompose')->assertHasNoErrors();

    expect(Group::query()->direct()->count())->toBe(1)
        ->and(Group::query()->direct()->sole()->directConversation->comments()->count())->toBe(2);
});

test('composing a multi-person message always creates a new group (no dedupe)', function (): void {
    $me = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $component = Livewire::actingAs($me)->test('pages::messages.index');

    $component->call('newMessage')->call('addRecipient', $a->id)->call('addRecipient', $b->id)
        ->set('reply', '<p>one</p>')->call('sendCompose')->assertHasNoErrors();

    $component->call('newMessage')->call('addRecipient', $a->id)->call('addRecipient', $b->id)
        ->set('reply', '<p>two</p>')->call('sendCompose')->assertHasNoErrors();

    expect(Group::query()->direct()->count())->toBe(2);
});

/* ----------------------------- add people ---------------------------- */

test('add people branches a new conversation and leaves the original intact', function (): void {
    $me = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $original = Group::findOrCreateDirect($me, [$a->id])->directConversation;
    $original->postComment('<p>original</p>', $me);

    Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $original])
        ->call('beginAddPeople')
        ->call('addRecipient', $b->id)
        ->set('reply', '<p>group hello</p>')
        ->call('sendCompose')
        ->assertHasNoErrors();

    $branched = Group::query()->direct()->whereNull('direct_key')->sole();

    expect(Group::query()->direct()->count())->toBe(2)
        ->and($original->fresh()->comments()->count())->toBe(1)
        ->and($branched->members()->count())->toBe(3);
});

test('removing added people back to the original recipient reuses the 1:1 thread', function (): void {
    $me = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $original = Group::findOrCreateDirect($me, [$a->id])->directConversation;
    $original->postComment('<p>original</p>', $me);

    Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $original])
        ->call('beginAddPeople')
        ->call('addRecipient', $b->id)
        ->call('removeRecipient', $b->id)
        ->set('reply', '<p>back to a</p>')
        ->call('sendCompose')
        ->assertHasNoErrors();

    expect(Group::query()->direct()->count())->toBe(1)
        ->and($original->fresh()->comments()->count())->toBe(2);
});

/* ------------------------------ banners ------------------------------ */

test('compose shows the existing-conversation banner for a known 1:1', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();
    Group::findOrCreateDirect($me, [$other->id]);

    Livewire::actingAs($me)
        ->test('pages::messages.index')
        ->call('newMessage')
        ->call('addRecipient', $other->id)
        ->assertSee('You already message');
});

test('compose shows the new-group banner for multiple fresh recipients', function (): void {
    $me = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();

    Livewire::actingAs($me)
        ->test('pages::messages.index')
        ->call('newMessage')
        ->call('addRecipient', $a->id)
        ->call('addRecipient', $b->id)
        ->assertSee('New group conversation');
});

test('add people shows the branch warning banner', function (): void {
    $me = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$a->id])->directConversation;

    Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $conversation])
        ->call('beginAddPeople')
        ->call('addRecipient', $b->id)
        ->assertSee('This starts a new conversation');
});

/* --------------------------- notifications --------------------------- */

test('sending a direct message notifies the other participants but not the author', function (): void {
    Notification::fake();

    $me = User::factory()->create();
    $other = User::factory()->create();

    Livewire::actingAs($me)
        ->test('pages::messages.index')
        ->call('newMessage')
        ->call('addRecipient', $other->id)
        ->set('reply', '<p>hi there</p>')
        ->call('sendCompose')
        ->assertHasNoErrors();

    Notification::assertSentTo($other, DirectMessageNotification::class);
    Notification::assertNotSentTo($me, DirectMessageNotification::class);
});

/* ------------------------------ unread ------------------------------- */

test('opening a conversation marks it read and clears its bell notifications', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($other, [$me->id])->directConversation;
    $conversation->postComment('<p>hello you</p>', $other);

    expect($conversation->unreadCountFor($me))->toBe(1)
        ->and($me->fresh()->unreadNotifications()->where('data->conversation_id', $conversation->id)->count())
        ->toBeGreaterThan(0);

    Livewire::actingAs($me)
        ->test('pages::messages.index')
        ->call('openConversation', $conversation->id);

    expect($conversation->fresh()->unreadCountFor($me))->toBe(0)
        ->and($me->fresh()->unreadNotifications()->where('data->conversation_id', $conversation->id)->count())
        ->toBe(0);
});

test('a sent message does not count as unread for its author', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;
    $conversation->postComment('<p>mine</p>', $me);

    expect($conversation->unreadCountFor($me))->toBe(0)
        ->and($conversation->unreadCountFor($other))->toBe(1)
        ->and($me->unreadDirectCount())->toBe(0)
        ->and($other->unreadDirectCount())->toBe(1);
});

/* --------------------- separation from named groups ------------------ */

test('direct groups are excluded from a user\'s named groups', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $direct = Group::findOrCreateDirect($me, [$other->id]);

    $visibleIds = $me->fresh()->groups()->where('groups.is_direct', false)->pluck('groups.id');

    expect($visibleIds)->not->toContain($direct->id);

    $this->actingAs($me)->get(route('groups.index'))->assertOk();
});
