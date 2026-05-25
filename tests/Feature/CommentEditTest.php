<?php

use App\Enums\GroupMembershipStatus;
use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Models\Conversation;
use App\Models\ConversationFile;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeEditScenario(): array
{
    $author = User::factory()->create(['name' => 'Author Ada']);
    $leader = User::factory()->create(['name' => 'Leader Leo']);
    $member = User::factory()->create(['name' => 'Member Mae']);

    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => GroupMessaging::ALL_MEMBERS,
    ]);
    $group->allUsers()->attach($author, ['role' => GroupRole::MEMBER, 'status' => GroupMembershipStatus::ACTIVE]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => GroupMembershipStatus::ACTIVE]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => GroupMembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
        'title' => 'Service thread',
    ]);

    return compact('group', 'conversation', 'author', 'leader', 'member');
}

beforeEach(function (): void {
    Storage::fake('digital-ocean', ['url' => 'https://stave.atl1.digitaloceanspaces.com']);
});

test('author can edit their own comment and edited_at is set', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>original</p>', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->assertSet('editingCommentId', $comment->id)
        ->set('editingText', '<p>rewritten</p>')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('editingCommentId', null);

    $fresh = $comment->fresh();
    expect($fresh->original_text)->toBe('<p>rewritten</p>')
        ->and($fresh->text)->toContain('rewritten')
        ->and($fresh->edited_at)->not->toBeNull();
});

test('group leader can edit another members comment', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author, 'leader' => $leader] = makeEditScenario();
    $comment = $conversation->postComment('<p>members message</p>', $author);

    Livewire::actingAs($leader)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->set('editingText', '<p>leader fixed this</p>')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($comment->fresh()->original_text)->toBe('<p>leader fixed this</p>');
});

test('a non-author non-leader member cannot start editing', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author, 'member' => $member] = makeEditScenario();
    $comment = $conversation->postComment('<p>mine</p>', $author);

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->assertForbidden();

    expect($comment->fresh()->original_text)->toBe('<p>mine</p>')
        ->and($comment->fresh()->edited_at)->toBeNull();
});

test('the pencil edit button is rendered for an editable comment', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $conversation->postComment('<p>hi</p>', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="edit-toggle"');
});

test('the pencil edit button is hidden for a user who cannot edit', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author, 'member' => $member] = makeEditScenario();
    $conversation->postComment('<p>hi</p>', $author);

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertDontSeeHtml('data-test="edit-toggle"');
});

test('the edited indicator renders after a comment has been edited', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>before</p>', $author);
    $comment->fresh()->markAsEdited();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->assertSeeHtml('data-test="edited-indicator"');
});

test('saving without changes does not set edited_at', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>same</p>', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($comment->fresh()->edited_at)->toBeNull();
});

test('cancelling an edit does not change the comment and discards new pending uploads', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>keep me</p>', $author);

    $component = Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->set('editingNewAttachment', UploadedFile::fake()->create('handout.pdf', 4, 'application/pdf'))
        ->assertHasNoErrors();

    $pendingIds = $component->get('editingPendingAttachmentIds');
    expect($pendingIds)->toHaveCount(1);
    $pendingId = $pendingIds[0];
    expect(ConversationFile::query()->whereKey($pendingId)->exists())->toBeTrue();

    $component
        ->set('editingText', '<p>different draft</p>')
        ->call('cancelEditing')
        ->assertSet('editingCommentId', null);

    expect($comment->fresh()->original_text)->toBe('<p>keep me</p>')
        ->and($comment->fresh()->edited_at)->toBeNull()
        ->and(ConversationFile::query()->whereKey($pendingId)->exists())->toBeFalse();
});

test('adding a new attachment during edit associates it with the comment on save', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>x</p>', $author);

    $component = Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->set('editingNewAttachment', UploadedFile::fake()->create('handout.pdf', 4, 'application/pdf'))
        ->assertHasNoErrors();

    $pendingId = $component->get('editingPendingAttachmentIds')[0];

    $component->call('saveEdit')->assertHasNoErrors();

    $file = ConversationFile::query()->find($pendingId);
    expect($file)->not->toBeNull()
        ->and($file->comment_id)->toBe($comment->id);
    expect($comment->fresh()->edited_at)->not->toBeNull();
});

test('removing an existing attachment during edit only deletes it on save', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>with file</p>', $author);
    /** @var ConversationFile $attachment */
    $attachment = ConversationFile::factory()
        ->for($conversation)
        ->create([
            'comment_id' => $comment->id,
            'uploader_id' => $author->id,
        ]);

    $component = Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->call('removeEditingExistingAttachment', $attachment->id);

    expect(ConversationFile::query()->whereKey($attachment->id)->exists())->toBeTrue();
    expect($component->get('editingRemovedAttachmentIds'))->toContain($attachment->id);

    $component->call('saveEdit')->assertHasNoErrors();

    expect(ConversationFile::query()->whereKey($attachment->id)->exists())->toBeFalse();
});

test('cancelling after marking an existing attachment for removal keeps the attachment', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>with file</p>', $author);
    /** @var ConversationFile $attachment */
    $attachment = ConversationFile::factory()
        ->for($conversation)
        ->create([
            'comment_id' => $comment->id,
            'uploader_id' => $author->id,
        ]);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->call('removeEditingExistingAttachment', $attachment->id)
        ->call('cancelEditing');

    expect(ConversationFile::query()->whereKey($attachment->id)->exists())->toBeTrue();
});

test('saveEdit shows an error when text and media are empty', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>filled</p>', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->set('editingText', '')
        ->call('saveEdit')
        ->assertHasErrors('editingText');

    expect($comment->fresh()->original_text)->toBe('<p>filled</p>')
        ->and($comment->fresh()->edited_at)->toBeNull();
});

test('removing an inline image from the edited text deletes the orphan ConversationFile', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('newImage', UploadedFile::fake()->image('photo.jpg'))
        ->set('reply', '<p>hello</p>')
        ->call('postReply')
        ->assertHasNoErrors();

    $comment = $conversation->fresh()->comments()->first();
    $inlineImage = ConversationFile::query()
        ->where('comment_id', $comment->id)
        ->where('is_inline_image', true)
        ->firstOrFail();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->set('editingText', '<p>hello only</p>')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect(ConversationFile::query()->whereKey($inlineImage->id)->exists())->toBeFalse()
        ->and($comment->fresh()->edited_at)->not->toBeNull();
});

test('starting edit while another comment is being edited cancels the first edit cleanly', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $first = $conversation->postComment('<p>first</p>', $author);
    $second = $conversation->postComment('<p>second</p>', $author);

    $component = Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $first->id)
        ->set('editingNewAttachment', UploadedFile::fake()->create('temp.pdf', 4, 'application/pdf'))
        ->assertHasNoErrors();

    $orphanId = $component->get('editingPendingAttachmentIds')[0];

    $component->call('startEditing', $second->id)
        ->assertSet('editingCommentId', $second->id)
        ->assertSet('editingPendingAttachmentIds', []);

    expect(ConversationFile::query()->whereKey($orphanId)->exists())->toBeFalse();
});

test('the mobile action sheet exposes an Edit row for editable comments', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>row</p>', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('openActions', $comment->id)
        ->assertSeeHtml('data-test="sheet-edit"');
});

test('the inline edit composer renders in place of the body while editing', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeEditScenario();
    $comment = $conversation->postComment('<p>visible body</p>', $author);

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('startEditing', $comment->id)
        ->assertSeeHtml('data-test="inline-edit-composer"');
});
