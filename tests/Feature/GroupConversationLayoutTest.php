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

/** Builds a group with a viewer member, a posted conversation. */
function buildLayoutScenario(int $extraMembers = 0): array
{
    $viewer = User::factory()->create(['name' => 'Viewer Vex']);
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
        'name' => 'Elders',
    ]);
    $group->allUsers()->attach($viewer, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    for ($i = 0; $i < $extraMembers; $i++) {
        $member = User::factory()->create(['name' => "Member {$i}"]);
        $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    }

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $viewer->id,
        'title' => 'Sunday Service Planning',
    ]);

    return [$group, $conversation, $viewer];
}

test('shows the tab bar with Conversation Files and Members tabs', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSuccessful()
        ->assertSee('Conversation', false)
        ->assertSee('Files', false)
        ->assertSee('Members', false)
        ->assertSeeHtml('data-test="comment-count-badge"');
});

test('the conversation count badge reflects the number of comments', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $conversation->postComment('first', $viewer);
    $conversation->postComment('second', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtmlInOrder([
            'data-test="comment-count-badge"',
            '2',
        ]);
});

test('the members rail renders with In conversation when there are contributors', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario(extraMembers: 2);
    $conversation->postComment('hello', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="members-rail"')
        ->assertSeeHtml('data-test="rail-in-conversation"')
        ->assertSeeHtml('data-test="rail-not-yet-posted"')
        ->assertSeeInOrder(['In conversation', 'Not yet posted']);
});

test('the rail omits Not yet posted when every member has posted', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $conversation->postComment('hi', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="rail-in-conversation"')
        ->assertDontSeeHtml('data-test="rail-not-yet-posted"');
});

test('the rail omits In conversation when no one has posted yet', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario(extraMembers: 1);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertDontSeeHtml('data-test="rail-in-conversation"')
        ->assertSeeHtml('data-test="rail-not-yet-posted"');
});

test('renders a stacked avatar overflow chip when there are more than four members', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario(extraMembers: 6);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSee('+3');
});

test('does not render the overflow chip when four or fewer members', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario(extraMembers: 2);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertDontSee('+1')
        ->assertDontSee('+0');
});

test('pinned strip renders when there is a pinned comment and dismisses on click', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $comment = $conversation->postComment('Pin this for visibility please.', $viewer);
    $comment->fresh()->pin($viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="pinned-strip"')
        ->assertSee('Pin this for visibility please.')
        ->assertSee('Pinned by Viewer Vex', false)
        ->call('dismissPinnedStrip')
        ->assertDontSeeHtml('data-test="pinned-strip"');
});

test('pinned strip does not render when there are no pinned comments', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $conversation->postComment('Just a normal message.', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertDontSeeHtml('data-test="pinned-strip"');
});

test('back chip links to the group page with an accessible label', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="back-to-group"')
        ->assertSeeHtml('aria-label="Back to Elders"')
        ->assertSee(route('groups.show', $group), false);
});

test('Members tab links to the groups page members tab', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSee(route('groups.show', ['group' => $group, 'tab' => 'members']), false);
});

// --- Phase G: empty state ---

test('empty state renders when the conversation has no comments', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="empty-state"')
        ->assertSee('No messages yet')
        ->assertSee('Kick things off');
});

test('empty state encourages read-only viewers differently than commenters', function (): void {
    $leader = User::factory()->create();
    $reader = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ONLY_LEADERS,
    ]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($reader, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create(['group_id' => $group->id, 'user_id' => $leader->id]);

    $this->actingAs($reader)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="empty-state"')
        ->assertDontSee('Kick things off')
        ->assertSee('Be the first to post here once messaging opens up');
});

test('empty state disappears once at least one comment exists', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $conversation->postComment('hello', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertDontSeeHtml('data-test="empty-state"');
});

// --- Phase G: accessibility ---

test('the hover toolbar exposes a toolbar landmark with a label', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $conversation->postComment('hi', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('role="toolbar"')
        ->assertSeeHtml('aria-label="Message actions"');
});

test('the prayer toggle reports its pressed state via aria-pressed', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $comment = $conversation->postComment('praying', $viewer);
    $comment->fresh()->togglePrayer();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('aria-pressed="true"');
});

test('icon-only buttons carry aria-labels', function (): void {
    [$group, $conversation, $viewer] = buildLayoutScenario();
    $comment = $conversation->postComment('hi', $viewer);
    $comment->fresh()->react('👍', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('aria-label="Add reaction"')
        ->assertSeeHtml('aria-label="Mark as prayer"')
        ->assertSeeHtml('aria-label="Pin to top"');
});
