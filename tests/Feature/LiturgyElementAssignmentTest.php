<?php

declare(strict_types=1);

use App\Livewire\Actions\CreateServiceFromTemplate;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Comments\Models\CommentNotificationSubscription;

uses(RefreshDatabase::class);

test('assignedUsers returns unique users with assignments', function (): void {
    $service = Service::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    LiturgyElement::factory()
        ->assignedTo($user1)
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    LiturgyElement::factory()
        ->assignedTo($user1)
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    LiturgyElement::factory()
        ->assignedTo($user2)
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    $assignees = $service->assignedUsers();

    expect($assignees)->toHaveCount(2);
    expect($assignees->pluck('id')->toArray())->toContain($user1->id, $user2->id);
});

test('assignedUsers returns empty collection when no assignments', function (): void {
    $service = Service::factory()->create();

    LiturgyElement::factory()
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    expect($service->assignedUsers())->toBeEmpty();
});

test('assignee is subscribed when element is created with assignee', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()
        ->assignedTo($user)
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    expect(CommentNotificationSubscription::query()
        ->where('subscriber_type', User::class)
        ->where('subscriber_id', $user->id)
        ->where('commentable_type', Service::class)
        ->where('commentable_id', $service->id)
        ->exists()
    )->toBeTrue();
});

test('assignee is unsubscribed when removed and has no other assignments', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $element = LiturgyElement::factory()
        ->assignedTo($user)
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    $element->update(['assignee_id' => null]);

    expect(CommentNotificationSubscription::query()
        ->where('subscriber_type', User::class)
        ->where('subscriber_id', $user->id)
        ->where('commentable_type', Service::class)
        ->where('commentable_id', $service->id)
        ->exists()
    )->toBeFalse();
});

test('assignee remains subscribed when one of multiple assignments is removed', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $element1 = LiturgyElement::factory()
        ->assignedTo($user)
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    LiturgyElement::factory()
        ->assignedTo($user)
        ->create(['liturgy_type' => Service::class, 'liturgy_id' => $service->id]);

    $element1->update(['assignee_id' => null]);

    expect(CommentNotificationSubscription::query()
        ->where('subscriber_type', User::class)
        ->where('subscriber_id', $user->id)
        ->where('commentable_type', Service::class)
        ->where('commentable_id', $service->id)
        ->exists()
    )->toBeTrue();
});

test('service creation from template subscribes all assignees', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $template = Template::factory()->create();

    LiturgyElement::factory()
        ->assignedTo($user1)
        ->create(['liturgy_type' => Template::class, 'liturgy_id' => $template->id]);

    LiturgyElement::factory()
        ->assignedTo($user2)
        ->create(['liturgy_type' => Template::class, 'liturgy_id' => $template->id]);

    app(CreateServiceFromTemplate::class)($template, Carbon::tomorrow());

    $service = Service::first();

    expect(CommentNotificationSubscription::query()
        ->where('subscriber_type', User::class)
        ->where('subscriber_id', $user1->id)
        ->where('commentable_type', Service::class)
        ->where('commentable_id', $service->id)
        ->exists()
    )->toBeTrue();

    expect(CommentNotificationSubscription::query()
        ->where('subscriber_type', User::class)
        ->where('subscriber_id', $user2->id)
        ->where('commentable_type', Service::class)
        ->where('commentable_id', $service->id)
        ->exists()
    )->toBeTrue();
});
