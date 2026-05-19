<?php

declare(strict_types=1);

use App\Models\LiturgyElement;
use App\Models\MutedCommentable;
use App\Models\Service;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\ServiceDiscussionCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('muting a service discussion suppresses ServiceDiscussionCommentNotification', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $muter = User::factory()->create();
    $other = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()->assignedTo($muter)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);
    LiturgyElement::factory()->assignedTo($other)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);

    MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Service::class,
        'commentable_id' => $service->id,
    ]);

    $service->comment('<p>A note</p>', $author);

    Notification::assertNotSentTo($muter, ServiceDiscussionCommentNotification::class);
    Notification::assertSentTo($other, ServiceDiscussionCommentNotification::class);
});

test('muting a service discussion does not suppress CommentMentionNotification', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $muter = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()->assignedTo($muter)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);

    MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Service::class,
        'commentable_id' => $service->id,
    ]);

    $service->comment(
        "<p>Hey <span data-mention=\"{$muter->id}\">@muter</span></p>",
        $author,
    );

    Notification::assertSentTo($muter, CommentMentionNotification::class);
    Notification::assertNotSentTo($muter, ServiceDiscussionCommentNotification::class);
});

test('unmuting a service discussion restores notification delivery', function (): void {
    $author = User::factory()->create();
    $muter = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()->assignedTo($muter)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);

    $mute = MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Service::class,
        'commentable_id' => $service->id,
    ]);

    $mute->delete();

    Notification::fake();

    $service->comment('<p>Welcome back</p>', $author);

    Notification::assertSentTo($muter, ServiceDiscussionCommentNotification::class);
});

test('one user muting a service discussion does not affect other assignees', function (): void {
    Notification::fake();

    $author = User::factory()->create();
    $muter = User::factory()->create();
    $other = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()->assignedTo($muter)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);
    LiturgyElement::factory()->assignedTo($other)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);

    MutedCommentable::create([
        'user_id' => $muter->id,
        'commentable_type' => Service::class,
        'commentable_id' => $service->id,
    ]);

    $service->comment('<p>Hi all</p>', $author);

    Notification::assertNotSentTo($muter, ServiceDiscussionCommentNotification::class);
    Notification::assertSentTo($other, ServiceDiscussionCommentNotification::class);
});

test('mute toggle Livewire component toggles mute state for a service', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('mute-toggle', [
        'commentable' => $service,
        'noun' => 'discussion',
    ])
        ->assertSet('isMuted', false)
        ->call('toggle')
        ->assertSet('isMuted', true);

    expect(MutedCommentable::query()->where([
        'user_id' => $user->id,
        'commentable_type' => Service::class,
        'commentable_id' => $service->id,
    ])->exists())->toBeTrue();

    Livewire::test('mute-toggle', [
        'commentable' => $service,
        'noun' => 'discussion',
    ])
        ->call('toggle')
        ->assertSet('isMuted', false);

    expect(MutedCommentable::query()->where([
        'user_id' => $user->id,
        'commentable_type' => Service::class,
        'commentable_id' => $service->id,
    ])->exists())->toBeFalse();
});
