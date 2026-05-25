<?php

use App\Enums\GroupMembershipStatus;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function buildMobileScenario(): array
{
    $viewer = User::factory()->create(['name' => 'Viewer Vex']);
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
        'name' => 'Elders',
    ]);
    $group->allUsers()->attach($viewer, ['role' => GroupRole::LEADER, 'status' => GroupMembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $viewer->id,
        'title' => 'Sunday Service Planning',
    ]);

    return [$group, $conversation, $viewer];
}

// --- Mobile header ---

test('mobile header renders the back chip, member count chip and expand toggle', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="conversation-header-mobile"')
        ->assertSeeHtml('data-test="back-to-group-mobile"')
        ->assertSeeHtml('data-test="member-count-chip"')
        ->assertSeeHtml('data-test="header-expand-toggle"');
});

test('toggleHeaderExpanded flips the headerExpanded property and reveals meta', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSet('headerExpanded', false)
        ->assertDontSeeHtml('data-test="header-meta-mobile"')
        ->call('toggleHeaderExpanded')
        ->assertSet('headerExpanded', true)
        ->assertSeeHtml('data-test="header-meta-mobile"')
        ->call('toggleHeaderExpanded')
        ->assertSet('headerExpanded', false);
});

// --- Mobile action sheet ---

test('openActions stores the comment id and closeActions clears it', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $comment = $conversation->postComment('hello', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSet('sheetCommentId', null)
        ->call('openActions', $comment->id)
        ->assertSet('sheetCommentId', $comment->id)
        ->call('closeActions')
        ->assertSet('sheetCommentId', null);
});

test('sheetComment resolves to the open comment', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $first = $conversation->postComment('first', $viewer);
    $second = $conversation->postComment('second', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('openActions', $second->id)
        ->tap(fn ($t) => expect($t->instance()->sheetComment->id)->toBe($second->id))
        ->call('openActions', $first->id)
        ->tap(fn ($t) => expect($t->instance()->sheetComment->id)->toBe($first->id))
        ->call('closeActions')
        ->tap(fn ($t) => expect($t->instance()->sheetComment)->toBeNull());
});

test('the action sheet renders the message actions trigger on every message', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $conversation->postComment('first', $viewer);
    $conversation->postComment('second', $viewer);

    $html = Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->html();

    expect(substr_count($html, 'data-test="message-actions-trigger"'))->toBe(2);
});

test('the action sheet renders prayer, pin and copy rows when open', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $comment = $conversation->postComment('hello there', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('openActions', $comment->id)
        ->assertSeeHtml('data-test="message-actions-sheet"')
        ->assertSeeHtml('data-test="sheet-prayer"')
        ->assertSeeHtml('data-test="sheet-pin"')
        ->assertSeeHtml('data-test="sheet-copy"')
        ->assertSee('Mark as prayer')
        ->assertSee('Pin to top')
        ->assertSee('Copy text');
});

test('the action sheet does not render a Reply in thread row in v1', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $comment = $conversation->postComment('hello there', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('openActions', $comment->id)
        ->assertDontSee('Reply in thread');
});

test('the action sheet swaps labels when the comment is already pinned or marked prayer', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $comment = $conversation->postComment('hello there', $viewer);
    $comment->fresh()->pin($viewer);
    $comment->fresh()->togglePrayer();

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('openActions', $comment->id)
        ->assertSee('Unmark as prayer')
        ->assertSee('Unpin from top');
});

test('reacting from the sheet toggles the user\'s membership in that reaction', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $comment = $conversation->postComment('hello', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('openActions', $comment->id)
        ->call('react', $comment->id, '🙏');

    expect($comment->fresh()->reactions()->where('reaction', '🙏')->count())->toBe(1);
});

// --- Mobile composer pill ---

test('the mobile composer pill renders for users who can comment', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="composer-mobile-pill"')
        ->assertSeeHtml('data-test="composer-collapse"');
});

test('the mobile composer pill is hidden for users who cannot comment', function (): void {
    $leader = User::factory()->create();
    $reader = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ONLY_LEADERS,
    ]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => GroupMembershipStatus::ACTIVE]);
    $group->allUsers()->attach($reader, ['role' => GroupRole::MEMBER, 'status' => GroupMembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create(['group_id' => $group->id, 'user_id' => $leader->id]);

    $this->actingAs($reader)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertDontSeeHtml('data-test="composer-mobile-pill"');
});

// --- Pinned strip mobile gating ---

test('the pinned strip is mobile-hidden by default and exposed when headerExpanded', function (): void {
    [$group, $conversation, $viewer] = buildMobileScenario();
    $comment = $conversation->postComment('Pin this one', $viewer);
    $comment->fresh()->pin($viewer);

    $component = Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation]);

    $collapsedHtml = $component->html();
    expect($collapsedHtml)->toContain('data-test="pinned-strip"')
        ->and($collapsedHtml)->toMatch('/<div[^>]*\bhidden\b[^>]*data-test="pinned-strip"/');

    $component->call('toggleHeaderExpanded');
    $expandedHtml = $component->html();
    expect($expandedHtml)->toContain('data-test="pinned-strip"')
        ->and($expandedHtml)->not->toMatch('/<div[^>]*\bhidden\b[^>]*data-test="pinned-strip"/');
});
