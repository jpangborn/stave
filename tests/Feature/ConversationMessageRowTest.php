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

function buildRowScenario(): array
{
    $viewer = User::factory()->create(['name' => 'Viewer Vex']);
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
        'name' => 'Elders',
    ]);
    $group->allUsers()->attach($viewer, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $viewer->id,
        'title' => 'Planning thread',
    ]);

    return [$group, $conversation, $viewer];
}

// --- Day dividers ---

test('day dividers separate comments posted on different days', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();

    $this->travelTo(now()->subDays(2)->setTime(10, 0));
    $conversation->postComment('two days ago', $viewer);

    $this->travelTo(now()->addDay()->setTime(10, 0));
    $conversation->postComment('yesterday', $viewer);

    $this->travelTo(now()->addDay()->setTime(10, 0));
    $conversation->postComment('today', $viewer);

    $this->travelBack();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'two days ago',
            'Yesterday',
            'yesterday',
            'Today',
            'today',
        ]);
});

test('comments posted on the same day get a single divider', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $conversation->postComment('first today', $viewer);
    $conversation->postComment('second today', $viewer);

    $rendered = Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->html();

    expect(substr_count($rendered, 'data-test="day-divider"'))->toBe(1);
});

// --- Own message emphasis ---

test('own messages render with the accent left border and a "(you)" suffix', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $conversation->postComment('mine', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test-mine="true"')
        ->assertSeeHtml('border-accent')
        ->assertSee('(you)');
});

test('other peoples messages do not get the mine attribute', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $other = User::factory()->create(['name' => 'Other Owen']);
    $group->allUsers()->attach($other, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $conversation->postComment('theirs', $other);

    $html = Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->html();

    expect($html)->not->toContain('data-test-mine="true"')
        ->and($html)->not->toContain('(you)');
});

test('the author role label appears in the message header line', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $conversation->postComment('hi', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSee('Leader');
});

// --- Pinned / Prayer badges ---

test('a pinned comment renders the Pinned badge', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $comment = $conversation->postComment('pin me', $viewer);
    $comment->fresh()->pin($viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="pinned-badge"')
        ->assertSee('Pinned');
});

test('a prayer comment renders the Prayer badge', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $comment = $conversation->postComment('praying', $viewer);
    $comment->fresh()->togglePrayer();

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="prayer-badge"')
        ->assertSee('Prayer');
});

test('an ordinary comment shows neither badge', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $conversation->postComment('plain', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertDontSeeHtml('data-test="pinned-badge"')
        ->assertDontSeeHtml('data-test="prayer-badge"');
});

// --- Reactions ---

test('a reaction chip the viewer added carries the mine flag', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $comment = $conversation->postComment('react to me', $viewer);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('react', $comment->id, '👍')
        ->assertSeeHtml('data-test-mine="true"');
});

test('a reaction chip the viewer did not add does not carry the mine flag', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $other = User::factory()->create();
    $group->allUsers()->attach($other, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $comment = $conversation->postComment('react to me', $other);
    $comment->fresh()->react('👍', $other);

    $html = Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->html();

    expect($html)->toContain('data-test="reaction-chip"')
        ->and(preg_match('/data-test="reaction-chip"[^>]*data-test-mine/', $html))->toBe(0);
});

test('clicking a reaction the viewer already gave removes it', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $comment = $conversation->postComment('react to me', $viewer);
    $comment->fresh()->react('👍', $viewer);

    expect($comment->fresh()->reactions()->count())->toBe(1);

    Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('react', $comment->id, '👍');

    expect($comment->fresh()->reactions()->count())->toBe(0);
});

test('reaction picker only renders the first six allowed reactions', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $conversation->postComment('hi', $viewer);

    $html = Livewire::actingAs($viewer)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->html();

    $allowed = config('comments.allowed_reactions');
    foreach (array_slice($allowed, 0, 6) as $expected) {
        expect($html)->toContain($expected);
    }
});

// --- Hover toolbar ---

test('the hover toolbar renders for users who can comment', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $conversation->postComment('hi', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="hover-toolbar"')
        ->assertSeeHtml('data-test="reply-stub"');
});

test('the hover toolbar does not render for read-only viewers (leaders-only group)', function (): void {
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ONLY_LEADERS,
    ]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $leader->id,
    ]);
    $conversation->postComment('leader-only post', $leader);

    $this->actingAs($member)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSuccessful()
        ->assertDontSeeHtml('data-test="hover-toolbar"');
});

// --- Scripture auto-linking ---

test('scripture references in comment bodies are rendered as scripture-ref links', function (): void {
    [$group, $conversation, $viewer] = buildRowScenario();
    $conversation->postComment('<p>Anchor the set in Romans 8:31 tonight.</p>', $viewer);

    $this->actingAs($viewer)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('class="scripture-ref"')
        ->assertSeeHtml('Romans 8:31</a>');
});
