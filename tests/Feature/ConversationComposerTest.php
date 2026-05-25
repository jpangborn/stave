<?php

use App\Enums\GroupMembershipStatus;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function buildComposerScenario(): array
{
    $member = User::factory()->create();
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => GroupMembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $member->id,
    ]);

    return [$group, $conversation, $member];
}

test('postComment persists is_prayer when the flag is passed', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    $comment = $conversation->postComment('<p>praying</p>', $member, isPrayer: true);

    expect($comment->fresh()->is_prayer)->toBeTrue();
});

test('postComment defaults is_prayer to false when no flag is passed', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    $comment = $conversation->postComment('<p>plain</p>', $member);

    expect($comment->fresh()->is_prayer)->toBeFalse();
});

test('submitting with replyIsPrayer on persists is_prayer on the comment', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>Praying for the Hawkins family</p>')
        ->set('replyIsPrayer', true)
        ->call('postReply')
        ->assertHasNoErrors();

    $comment = $conversation->fresh()->comments()->first();
    expect($comment->is_prayer)->toBeTrue();
});

test('submitting with the prayer flag off creates a non-prayer comment', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>routine note</p>')
        ->call('postReply')
        ->assertHasNoErrors();

    $comment = $conversation->fresh()->comments()->first();
    expect($comment->is_prayer)->toBeFalse();
});

test('replyIsPrayer resets to false after a successful send', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>praying again</p>')
        ->set('replyIsPrayer', true)
        ->call('postReply')
        ->assertSet('replyIsPrayer', false)
        ->assertSet('reply', '');
});

test('replyIsPrayer is preserved when a validation error occurs', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>   </p>')
        ->set('replyIsPrayer', true)
        ->call('postReply')
        ->assertHasErrors('reply')
        ->assertSet('replyIsPrayer', true);
});

test('the composer renders the prayer toggle button', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="composer-prayer-toggle"');
});

test('the prayer toggle marks active when replyIsPrayer is true', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('replyIsPrayer', true)
        ->assertSeeHtml('data-test-active="true"')
        ->assertSee('Sending as prayer');
});

test('the composer renders the keyboard shortcut hint', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    $this->actingAs($member)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="composer-shortcut-hint"')
        ->assertSee('to send');
});

test('the composer renders the mention button', function (): void {
    [$group, $conversation, $member] = buildComposerScenario();

    $this->actingAs($member)
        ->get(route('groups.conversations.show', ['group' => $group, 'conversation' => $conversation]))
        ->assertSeeHtml('data-test="composer-mention"')
        ->assertSee('Mention');
});

test('the composer is hidden for users who cannot comment', function (): void {
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
        ->assertDontSeeHtml('data-test="composer-prayer-toggle"')
        ->assertDontSeeHtml('data-test="composer-shortcut-hint"');
});

test('omitting newImageModel hides the image attach button', function (): void {
    $rendered = Blade::render(<<<'BLADE'
        <x-conversation-composer
            editor-model="reply"
            new-attachment-model="newAttachment"
            :pending-attachments="$pendingAttachments"
            submit-action="postReply"
        />
    BLADE, ['pendingAttachments' => collect()]);

    expect($rendered)
        ->not->toContain('data-test="composer-image-button"')
        ->and($rendered)->toContain('data-test="composer-attach-button"');
});

test('omitting newAttachmentModel hides the file attach button', function (): void {
    $rendered = Blade::render(<<<'BLADE'
        <x-conversation-composer
            editor-model="reply"
            new-image-model="newImage"
            :pending-images="$pendingImages"
            submit-action="postReply"
        />
    BLADE, ['pendingImages' => collect()]);

    expect($rendered)
        ->toContain('data-test="composer-image-button"')
        ->and($rendered)->not->toContain('data-test="composer-attach-button"');
});

test('allowPrayer false hides the prayer toggle even with a prayer model', function (): void {
    $rendered = Blade::render(<<<'BLADE'
        <x-conversation-composer
            editor-model="reply"
            prayer-model="replyIsPrayer"
            :allow-prayer="false"
            submit-action="postReply"
        />
    BLADE);

    expect($rendered)->not->toContain('data-test="composer-prayer-toggle"');
});

test('allowMentions false hides the mention button', function (): void {
    $rendered = Blade::render(<<<'BLADE'
        <x-conversation-composer
            editor-model="reply"
            :allow-mentions="false"
            submit-action="postReply"
        />
    BLADE);

    expect($rendered)->not->toContain('data-test="composer-mention"');
});
