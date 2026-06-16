<?php

declare(strict_types=1);

use App\Events\DirectMessageReactionToggled;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: User, 2: Conversation}
 */
function directThread(): array
{
    $me = User::factory()->create(['name' => 'Me Myself']);
    $other = User::factory()->create(['name' => 'Otto Other']);
    $conversation = Group::findOrCreateDirect($me, [$other->id])->directConversation;

    return [$me, $other, $conversation];
}

test('a participant can react to a message and the chip carries the mine flag', function (): void {
    [$me, $other, $conversation] = directThread();
    $comment = $conversation->postComment('<p>react to me</p>', $other);

    Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $conversation])
        ->call('react', $comment->id, '👍')
        ->assertHasNoErrors()
        ->assertSeeHtml('data-test-mine="true"');

    expect($comment->fresh()->reactions()->count())->toBe(1);
});

test('clicking a reaction the participant already gave removes it', function (): void {
    [$me, $other, $conversation] = directThread();
    $comment = $conversation->postComment('<p>react to me</p>', $other);
    $comment->fresh()->react('👍', $me);

    expect($comment->fresh()->reactions()->count())->toBe(1);

    Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $conversation])
        ->call('react', $comment->id, '👍');

    expect($comment->fresh()->reactions()->count())->toBe(0);
});

test('reacting with a disallowed value is rejected', function (): void {
    [$me, $other, $conversation] = directThread();
    $comment = $conversation->postComment('<p>react to me</p>', $other);

    Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $conversation])
        ->call('react', $comment->id, '<script>alert(1)</script>')
        ->assertStatus(422);

    expect($comment->fresh()->reactions()->count())->toBe(0);
});

test('a non-participant cannot react even by forging the active conversation id', function (): void {
    [$me, , $conversation] = directThread();
    $comment = $conversation->postComment('<p>private</p>', $me);
    $stranger = User::factory()->create();

    Livewire::actingAs($stranger)
        ->test('pages::messages.index')
        ->set('activeConversationId', $conversation->id)
        ->call('react', $comment->id, '👍');

    expect($comment->fresh()->reactions()->count())->toBe(0);
});

test('reacting broadcasts to the other participant but not the reactor', function (): void {
    Event::fake([DirectMessageReactionToggled::class]);

    [$me, $other, $conversation] = directThread();
    $comment = $conversation->postComment('<p>react to me</p>', $other);

    Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $conversation])
        ->call('react', $comment->id, '👍');

    Event::assertDispatched(
        DirectMessageReactionToggled::class,
        function (DirectMessageReactionToggled $event) use ($conversation, $me, $other): bool {
            $channels = collect($event->broadcastOn())->map(fn ($channel): string => $channel->name);

            return $event->conversation->is($conversation)
                && $event->authorId === $me->id
                && $channels->contains("private-App.Models.User.{$other->id}")
                && ! $channels->contains("private-App.Models.User.{$me->id}");
        },
    );
});

test('a reaction chip shows everyone who reacted with the viewer listed first', function (): void {
    [$me, $other, $conversation] = directThread();
    $comment = $conversation->postComment('<p>react to me</p>', $other);
    $comment->fresh()->react('👍', $other);
    $comment->fresh()->react('👍', $me);

    $html = Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $conversation])
        ->html();

    expect($comment->fresh()->reactions()->count())->toBe(2)
        ->and($html)->toContain('You, Otto Other');
});

test('refreshReactions re-renders chips for a reaction added out of band', function (): void {
    [$me, $other, $conversation] = directThread();
    $comment = $conversation->postComment('<p>react to me</p>', $me);

    $component = Livewire::actingAs($me)
        ->test('pages::messages.index', ['conversation' => $conversation])
        ->assertDontSeeHtml('data-test="reaction-chip"');

    $comment->fresh()->react('🔥', $other);

    $component->call('refreshReactions', $conversation->id)
        ->assertSeeHtml('data-test="reaction-chip"')
        ->assertSeeHtml('🔥');
});
