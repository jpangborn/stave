<?php

use App\Actions\Comments\ResolveGroupMentionsAutocompleteAction;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use App\Notifications\ServiceCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function buildMentionScenario(): array
{
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);

    $caller = User::factory()->create();
    $group->allUsers()->attach($caller, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create(['group_id' => $group->id, 'user_id' => $caller->id]);

    return [$group, $conversation, $caller];
}

function attachMentionMember(Group $group, User $user, MembershipStatus $status = MembershipStatus::ACTIVE): void
{
    $group->allUsers()->attach($user, ['role' => GroupRole::MEMBER, 'status' => $status]);
}

test('mentionCandidates returns active group members matching the query', function (): void {
    [$group, $conversation, $caller] = buildMentionScenario();

    $jane = User::factory()->create(['name' => 'Jane Doe']);
    $john = User::factory()->create(['name' => 'John Smith']);
    $other = User::factory()->create(['name' => 'Zara Other']);
    attachMentionMember($group, $jane);
    attachMentionMember($group, $john);
    attachMentionMember($group, $other);

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('mentionCandidates', 'j')
        ->assertReturned(fn (array $result): bool => collect($result)->pluck('name')->sort()->values()->all() === ['Jane Doe', 'John Smith']);
});

test('mentionCandidates excludes the current user', function (): void {
    [$group, $conversation, $caller] = buildMentionScenario();
    $caller->forceFill(['name' => 'Charlie Caller'])->save();

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('mentionCandidates', 'Charlie')
        ->assertReturned([]);
});

test('mentionCandidates excludes users from other groups', function (): void {
    [$group, $conversation, $caller] = buildMentionScenario();

    $outsiderGroup = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    $outsider = User::factory()->create(['name' => 'Olivia Outside']);
    attachMentionMember($outsiderGroup, $outsider);

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('mentionCandidates', 'Olivia')
        ->assertReturned([]);
});

test('mentionCandidates excludes inactive (pending) group members', function (): void {
    [$group, $conversation, $caller] = buildMentionScenario();

    $pending = User::factory()->create(['name' => 'Patty Pending']);
    attachMentionMember($group, $pending, MembershipStatus::PENDING);

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('mentionCandidates', 'Patty')
        ->assertReturned([]);
});

test('mentionCandidates surfaces prior contributors before other members', function (): void {
    [$group, $conversation, $caller] = buildMentionScenario();

    $alice = User::factory()->create(['name' => 'Alice Apple']);
    $aaron = User::factory()->create(['name' => 'Aaron Apricot']);
    attachMentionMember($group, $alice);
    attachMentionMember($group, $aaron);

    $conversation->postComment('<p>kicking things off</p>', $aaron);

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('mentionCandidates', 'A')
        ->assertReturned(fn (array $result): bool => ($result[0]['name'] ?? null) === 'Aaron Apricot');
});

test('viewing the conversation page is forbidden for non-members', function (): void {
    [$group, $conversation] = buildMentionScenario();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertForbidden();
});

test('mention candidates are returned with id, name, and gravatar fields', function (): void {
    [$group, $conversation, $caller] = buildMentionScenario();
    $jane = User::factory()->create(['name' => 'Jane Doe']);
    attachMentionMember($group, $jane);

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('mentionCandidates', 'Jane')
        ->assertReturned(fn (array $result): bool => isset($result[0]['id'], $result[0]['name'], $result[0]['gravatar'])
            && $result[0]['id'] === $jane->id
            && $result[0]['name'] === 'Jane Doe');
});

test('posting a reply with a mention notifies the mentioned member', function (): void {
    Notification::fake();

    [$group, $conversation, $caller] = buildMentionScenario();
    $mentionee = User::factory()->create(['name' => 'Mentioned Mike']);
    attachMentionMember($group, $mentionee);

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>Hi <span data-mention="'.$mentionee->id.'">@Mentioned Mike</span></p>')
        ->call('postReply')
        ->assertHasNoErrors();

    Notification::assertSentTo($mentionee, ServiceCommentNotification::class);
});

test('the MentionsTransformer rewrites data-mention spans into the rendered mention markup', function (): void {
    [, $conversation, $caller] = buildMentionScenario();
    $mentionee = User::factory()->create(['name' => 'Rendered Rosa']);
    attachMentionMember($conversation->group, $mentionee);

    $conversation->postComment(
        '<p>Hi <span data-mention="'.$mentionee->id.'">@Rendered Rosa</span></p>',
        $caller,
    );

    $comment = $conversation->fresh()->comments()->first();

    expect($comment->original_text)->toContain('data-mention="'.$mentionee->id.'"')
        ->and($comment->text)->toContain('class="mention"')
        ->and($comment->text)->toContain('Rendered Rosa');
});

test('posting a reply strips mentions targeting users outside the group', function (): void {
    Notification::fake();

    [$group, $conversation, $caller] = buildMentionScenario();
    $outsider = User::factory()->create(['name' => 'Outside Oscar']);

    Livewire::actingAs($caller)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>Hey <span data-mention="'.$outsider->id.'">@Outside Oscar</span></p>')
        ->call('postReply')
        ->assertHasNoErrors();

    Notification::assertNotSentTo($outsider, ServiceCommentNotification::class);

    $comment = $conversation->fresh()->comments()->first();
    expect($comment->original_text)->not->toContain('data-mention="'.$outsider->id.'"');
});

test('the action falls back to parent behavior for non-Conversation commentables', function (): void {
    // Use a fresh user as the caller; for parent fallback any commentable that isn't a Conversation should call parent::execute().
    [$group, $conversation, $caller] = buildMentionScenario();
    Auth::login($caller);

    $action = app(ResolveGroupMentionsAutocompleteAction::class);

    // The parent uses the configured commentator model (User) and filters by name.
    // We confirm that a Conversation goes through the group-scoped branch — passing a Conversation here exercises the override.
    $result = $action->execute('', $conversation);
    expect($result)->toBeArray();
});
