<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Conversation;
use App\Models\ConversationFile;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mews\Purifier\Facades\Purifier;

uses(RefreshDatabase::class);

function makeConversation(GroupMessaging $messaging = GroupMessaging::ALL_MEMBERS): array
{
    $author = User::factory()->create();
    $member = User::factory()->create();
    $leader = User::factory()->create();
    $outsider = User::factory()->create();

    $group = Group::factory()->create([
        'visibility' => GroupVisibility::PUBLIC,
        'messaging' => $messaging,
    ]);

    $group->allUsers()->attach($author, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($member, ['role' => GroupRole::MEMBER, 'status' => MembershipStatus::ACTIVE]);
    $group->allUsers()->attach($leader, ['role' => GroupRole::LEADER, 'status' => MembershipStatus::ACTIVE]);

    $conversation = Conversation::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    return compact('group', 'conversation', 'author', 'member', 'leader', 'outsider');
}

function pdfFile(int $sizeInKb = 4): File
{
    $content = "%PDF-1.4\n".str_repeat("\x00", max(0, ($sizeInKb * 1024) - 9));

    return File::createWithContent('handout.pdf', $content)->mimeType('application/pdf');
}

beforeEach(function (): void {
    Storage::fake('digital-ocean', ['url' => 'https://stave.atl1.digitaloceanspaces.com']);
});

it('uploads an inline image and stages it for the next reply', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('newImage', UploadedFile::fake()->image('photo.jpg', 200, 200))
        ->assertHasNoErrors();

    expect(ConversationFile::query()->count())->toBe(1);
    expect(ConversationFile::query()->first()->is_inline_image)->toBeTrue();
    expect(ConversationFile::query()->first()->comment_id)->toBeNull();
});

it('links a staged inline image to the comment on send and embeds it in the body', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('newImage', UploadedFile::fake()->image('photo.jpg'))
        ->set('reply', '<p>Check this out</p>')
        ->call('postReply')
        ->assertHasNoErrors();

    $comment = $conversation->fresh()->comments()->first();
    expect($comment)->not->toBeNull();
    expect($comment->text)->toContain('data-conversation-file-id=');

    $file = ConversationFile::query()->first();
    expect($file->comment_id)->toBe($comment->id);
    expect($file->is_inline_image)->toBeTrue();
});

it('excludes inline images from the sidebar attachments list', function (): void {
    ['conversation' => $conversation, 'author' => $author] = makeConversation();

    ConversationFile::factory()->inlineImage()->for($conversation)->create(['uploader_id' => $author->id]);
    $attachment = ConversationFile::factory()->for($conversation)->create(['uploader_id' => $author->id]);

    expect($conversation->attachments()->pluck('id')->all())->toBe([$attachment->id]);
});

it('attaches non-image files via the composer and shows them under the comment and in the sidebar', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('newAttachment', pdfFile())
        ->set('reply', '<p>Here is the handout</p>')
        ->call('postReply')
        ->assertHasNoErrors();

    $comment = $conversation->fresh()->comments()->first();
    expect($comment->attachments)->toHaveCount(1);
    expect($comment->attachments->first()->original_name)->toBe('handout.pdf');

    expect($conversation->attachments)->toHaveCount(1);
});

it('uploads a standalone file from the sidebar with no comment', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('standaloneUpload', pdfFile())
        ->assertHasNoErrors();

    expect($conversation->fresh()->comments()->count())->toBe(0);
    expect($conversation->attachments)->toHaveCount(1);
    expect($conversation->attachments->first()->comment_id)->toBeNull();
});

it('does not let users without comment permission upload to the sidebar', function (): void {
    // Members of a leader-only-messaging group can VIEW but cannot COMMENT.
    ['group' => $group, 'conversation' => $conversation, 'member' => $reader] = makeConversation(GroupMessaging::ONLY_LEADERS);

    Livewire::actingAs($reader)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('standaloneUpload', pdfFile())
        ->assertForbidden();
});

it('allows the uploader to delete their own attachment', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'member' => $member] = makeConversation();
    $file = ConversationFile::factory()->for($conversation)->create(['uploader_id' => $member->id]);

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('deleteAttachment', $file->id)
        ->assertHasNoErrors();

    expect(ConversationFile::query()->find($file->id))->toBeNull();
});

it('allows a group leader to delete any attachment', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'member' => $member, 'leader' => $leader] = makeConversation();
    $file = ConversationFile::factory()->for($conversation)->create(['uploader_id' => $member->id]);

    Livewire::actingAs($leader)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('deleteAttachment', $file->id)
        ->assertHasNoErrors();

    expect(ConversationFile::query()->find($file->id))->toBeNull();
});

it('forbids deleting an attachment uploaded by someone else when the user is not a leader', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author, 'member' => $member] = makeConversation();
    $file = ConversationFile::factory()->for($conversation)->create(['uploader_id' => $author->id]);

    Livewire::actingAs($member)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->call('deleteAttachment', $file->id)
        ->assertForbidden();

    expect(ConversationFile::query()->find($file->id))->not->toBeNull();
});

it('removes the S3 object when a ConversationFile is deleted', function (): void {
    ['conversation' => $conversation, 'author' => $author] = makeConversation();

    Storage::disk('digital-ocean')->put('conversations/x/test.pdf', 'pdf-bytes');
    $file = ConversationFile::factory()->for($conversation)->create([
        'uploader_id' => $author->id,
        'path' => 'conversations/x/test.pdf',
    ]);

    Storage::disk('digital-ocean')->assertExists('conversations/x/test.pdf');

    $file->delete();

    Storage::disk('digital-ocean')->assertMissing('conversations/x/test.pdf');
});

it('cascades attachment deletion when a comment is deleted', function (): void {
    ['conversation' => $conversation, 'author' => $author] = makeConversation();

    $comment = $conversation->postComment('<p>hello</p>', $author);
    $file = ConversationFile::factory()->for($conversation)->create([
        'comment_id' => $comment->id,
        'uploader_id' => $author->id,
    ]);

    $comment->delete();

    expect(ConversationFile::query()->find($file->id))->toBeNull();
});

it('image upload rules reject files over 5 MB', function (): void {
    $oversized = UploadedFile::fake()->image('big.jpg')->size(6000);

    $errors = validator(
        ['file' => $oversized],
        ['file' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120']],
    )->errors()->all();

    expect($errors)->not->toBeEmpty();
    expect(implode(' ', $errors))->toContain('5120');
});

it('rejects disallowed file types in the composer', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    $exe = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('newAttachment', $exe)
        ->assertHasErrors(['newAttachment']);

    expect(ConversationFile::query()->count())->toBe(0);
});

it('blocks posting an empty reply with no attachments', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('reply', '<p>   </p>')
        ->call('postReply')
        ->assertHasErrors('reply');
});

it('allows posting a comment with only an attachment and no text', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('newAttachment', pdfFile())
        ->set('reply', '')
        ->call('postReply')
        ->assertHasNoErrors();

    expect($conversation->fresh()->comments()->count())->toBe(1);
});

it('removes a pending attachment when the user discards it before sending', function (): void {
    ['group' => $group, 'conversation' => $conversation, 'author' => $author] = makeConversation();

    $component = Livewire::actingAs($author)
        ->test('pages::groups.conversations.show', ['group' => $group, 'conversation' => $conversation])
        ->set('newAttachment', pdfFile());

    $fileId = ConversationFile::query()->first()->id;

    $component->call('removePendingAttachment', $fileId)
        ->assertHasNoErrors();

    expect(ConversationFile::query()->find($fileId))->toBeNull();
});

it('sanitizer keeps img tags and strips script tags', function (): void {
    $dirty = '<p>hi <img src="https://stave.atl1.digitaloceanspaces.com/a.jpg" data-conversation-file-id="9"> <script>alert(1)</script></p>';
    $clean = Purifier::clean($dirty, 'comment_body');

    expect($clean)->toContain('<img');
    expect($clean)->toContain('data-conversation-file-id="9"');
    expect($clean)->not->toContain('<script');
});

it('sanitizer preserves external https links but strips javascript: hrefs', function (): void {
    $dirty = '<p><a href="https://example.com">ok</a> <a href="javascript:alert(1)">bad</a></p>';
    $clean = Purifier::clean($dirty, 'comment_body');

    expect($clean)->toContain('href="https://example.com"');
    expect($clean)->not->toContain('javascript:');
});

it('drops http img sources because comments only accept https', function (): void {
    $dirty = '<img src="http://example.com/x.jpg">';
    $clean = Purifier::clean($dirty, 'comment_body');

    expect($clean)->not->toContain('http://example.com');
});
