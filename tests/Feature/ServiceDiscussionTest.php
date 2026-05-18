<?php

declare(strict_types=1);

use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('authenticated user can view discussion component', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->assertStatus(200);
});

test('posting a reply creates a comment attributed to the current user', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->set('reply', '<p>Test comment content</p>')
        ->call('postReply')
        ->assertHasNoErrors()
        ->assertSet('reply', '');

    $comment = $service->fresh()->comments()->first();
    expect($comment)->not->toBeNull()
        ->and($comment->text)->toContain('Test comment content')
        ->and($comment->commentator->id)->toBe($user->id);
});

test('empty replies are rejected with a validation error', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->set('reply', '<p>   </p>')
        ->call('postReply')
        ->assertHasErrors('reply');

    expect($service->fresh()->comments()->count())->toBe(0);
});

test('the composer hides attach buttons and the prayer toggle', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->assertDontSeeHtml('data-test="composer-image-button"')
        ->assertDontSeeHtml('data-test="composer-attach-button"')
        ->assertDontSeeHtml('data-test="composer-prayer-toggle"');
});

test('the composer renders the mention button', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->assertSeeHtml('data-test="composer-mention"');
});

test('the empty state appears when no comments exist', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->assertSeeHtml('data-test="empty-state"')
        ->assertSee('No messages yet');
});

test('a day divider appears once comments exist', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>hello</p>', $user);

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->assertSeeHtml('data-test="day-divider"')
        ->assertSee('Today');
});

test('reacting twice with the same emoji removes the reaction', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>hi</p>', $user);
    $comment = $service->comments()->first();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('react', $comment->id, '👍')
        ->call('react', $comment->id, '👍');

    expect($comment->fresh()->reactions()->count())->toBe(0);
});

test('reactions outside the allowed set are rejected', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>hi</p>', $user);
    $comment = $service->comments()->first();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('react', $comment->id, '💀')
        ->assertStatus(422);
});

test('author can edit their own comment and edited_at is set', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>original</p>', $user);
    $comment = $service->comments()->first();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('startEditing', $comment->id)
        ->assertSet('editingCommentId', $comment->id)
        ->set('editingText', '<p>rewritten</p>')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('editingCommentId', null);

    $fresh = $comment->fresh();
    expect($fresh->original_text)->toBe('<p>rewritten</p>')
        ->and($fresh->edited_at)->not->toBeNull();
});

test('a non-author cannot start editing', function (): void {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>mine</p>', $author);
    $comment = $service->comments()->first();

    $this->actingAs($other);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('startEditing', $comment->id)
        ->assertStatus(403);
});

test('cancelEditing clears editing state without saving', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>original</p>', $user);
    $comment = $service->comments()->first();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('startEditing', $comment->id)
        ->set('editingText', '<p>discarded</p>')
        ->call('cancelEditing')
        ->assertSet('editingCommentId', null);

    expect($comment->fresh()->original_text)->toBe('<p>original</p>');
});

test('author can delete their own comment', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>delete me</p>', $user);
    $comment = $service->comments()->first();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('confirmDeleteComment', $comment->id)
        ->assertSet('commentToDeleteId', $comment->id)
        ->call('deleteComment')
        ->assertSet('commentToDeleteId', null);

    expect($service->fresh()->comments()->count())->toBe(0);
});

test('a non-author cannot delete a comment', function (): void {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>theirs</p>', $author);
    $comment = $service->comments()->first();

    $this->actingAs($other);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('confirmDeleteComment', $comment->id)
        ->assertStatus(403);

    expect($service->fresh()->comments()->count())->toBe(1);
});

test('openActions sets the sheet comment id', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>hi</p>', $user);
    $comment = $service->comments()->first();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('openActions', $comment->id)
        ->assertSet('sheetCommentId', $comment->id);
});

test('mention candidates are scoped to liturgy element assignees', function (): void {
    $service = Service::factory()->create();
    $caller = User::factory()->create(['name' => 'Caller Carla']);
    $assignee = User::factory()->create(['name' => 'Assigned Alex']);
    $outsider = User::factory()->create(['name' => 'Outside Ollie']);

    LiturgyElement::factory()
        ->assignedTo($assignee)
        ->create([
            'liturgy_type' => $service->getMorphClass(),
            'liturgy_id' => $service->id,
        ]);

    $this->actingAs($caller);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('mentionCandidates', '')
        ->assertReturned(function (array $result) use ($assignee): bool {
            $ids = collect($result)->pluck('id')->all();

            return $ids === [$assignee->id];
        });

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('mentionCandidates', 'Ollie')
        ->assertReturned([]);
});

test('mentions targeting users outside the service are sanitized away', function (): void {
    $service = Service::factory()->create();
    $caller = User::factory()->create();
    $assignee = User::factory()->create(['name' => 'Inside Iris']);
    $outsider = User::factory()->create(['name' => 'Outside Oscar']);

    LiturgyElement::factory()
        ->assignedTo($assignee)
        ->create([
            'liturgy_type' => $service->getMorphClass(),
            'liturgy_id' => $service->id,
        ]);

    $this->actingAs($caller);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->set('reply', '<p>Hi <span data-mention="'.$outsider->id.'">@Outside Oscar</span> and <span data-mention="'.$assignee->id.'">@Inside Iris</span></p>')
        ->call('postReply')
        ->assertHasNoErrors();

    $comment = $service->fresh()->comments()->first();
    expect($comment->original_text)
        ->toContain('data-mention="'.$assignee->id.'"')
        ->not->toContain('data-mention="'.$outsider->id.'"');
});
