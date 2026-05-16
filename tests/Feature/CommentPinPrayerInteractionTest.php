<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function buildInteractionScenario(): array
{
    $author = User::factory()->create(['name' => 'Author Ada']);
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    $group->allUsers()->attach($author, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
        'title' => 'Service thread',
    ]);

    return [$group, $conversation, $author];
}

// --- pinComment ---

test('an authorized user can pin a comment via the Livewire action', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $comment = $conversation->postComment('pin me', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('pinComment', $comment->id);

    expect($comment->fresh()->isPinned())->toBeTrue()
        ->and($comment->fresh()->pinned_by_user_id)->toBe($author->id);
});

test('pinning re-opens the pinned strip even if the user previously dismissed it', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $comment = $conversation->postComment('pin me later', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('dismissPinnedStrip')
        ->assertSet('pinnedStripOpen', false)
        ->call('pinComment', $comment->id)
        ->assertSet('pinnedStripOpen', true)
        ->assertSeeHtml('data-test="pinned-strip"');
});

test('a non-author non-leader member cannot pin a comment', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $other = User::factory()->create();
    $group->allUsers()->attach($other, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $comment = $conversation->postComment('not yours', $author);

    Livewire::actingAs($other)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('pinComment', $comment->id)
        ->assertForbidden();

    expect($comment->fresh()->isPinned())->toBeFalse();
});

// --- unpinComment ---

test('an authorized user can unpin a pinned comment', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $comment = $conversation->postComment('pinned', $author);
    $comment->fresh()->pin($author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('unpinComment', $comment->id);

    expect($comment->fresh()->isPinned())->toBeFalse()
        ->and($comment->fresh()->pinned_by_user_id)->toBeNull();
});

test('a non-author non-leader member cannot unpin a comment', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $other = User::factory()->create();
    $group->allUsers()->attach($other, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $comment = $conversation->postComment('not yours', $author);
    $comment->fresh()->pin($author);

    Livewire::actingAs($other)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('unpinComment', $comment->id)
        ->assertForbidden();

    expect($comment->fresh()->isPinned())->toBeTrue();
});

// --- togglePrayer ---

test('any active group member can mark a comment as prayer', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $member = User::factory()->create();
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $comment = $conversation->postComment('pray for this', $author);

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('togglePrayer', $comment->id);

    expect($comment->fresh()->is_prayer)->toBeTrue();
});

test('togglePrayer flips an already-prayer comment back off', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $comment = $conversation->postComment('pray for this', $author);
    $comment->fresh()->togglePrayer();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('togglePrayer', $comment->id);

    expect($comment->fresh()->is_prayer)->toBeFalse();
});

// --- View rendering ---

test('the prayer toggle button is marked active when a comment is_prayer', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $comment = $conversation->postComment('praying', $author);
    $comment->fresh()->togglePrayer();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="prayer-toggle"')
        ->assertSeeHtml('data-test-active="true"');
});

test('the pin toggle is rendered for an unpinned comment', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $conversation->postComment('hi', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="pin-toggle"')
        ->assertDontSeeHtml('data-test="unpin-toggle"');
});

test('the unpin toggle is rendered for a pinned comment', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $comment = $conversation->postComment('pinned', $author);
    $comment->fresh()->pin($author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="unpin-toggle"')
        ->assertDontSeeHtml('data-test="pin-toggle"');
});

test('the pin toggle is hidden for a member who cannot pin', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $other = User::factory()->create();
    $group->allUsers()->attach($other, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $conversation->postComment('hi', $author);

    Livewire::actingAs($other)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertDontSeeHtml('data-test="pin-toggle"')
        ->assertDontSeeHtml('data-test="unpin-toggle"');
});

test('the prayer toggle button is rendered for active group members', function (): void {
    [$group, $conversation, $author] = buildInteractionScenario();
    $member = User::factory()->create();
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $conversation->postComment('hi', $author);

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="prayer-toggle"');
});
