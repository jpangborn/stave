<?php

declare(strict_types=1);

use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\User;
use App\Recipients\ResolveServiceDiscussionRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns assigned users except the author', function (): void {
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $service = Service::factory()->create();

    LiturgyElement::factory()->assignedTo($author)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);
    LiturgyElement::factory()->assignedTo($assignee)->create([
        'liturgy_type' => Service::class, 'liturgy_id' => $service->id,
    ]);

    $recipients = (new ResolveServiceDiscussionRecipients())($service, $author);
    $ids = $recipients->pluck('id')->all();

    expect($ids)->toContain($assignee->id);
    expect($ids)->not->toContain($author->id);
});

test('returns empty collection when service has no assignees', function (): void {
    $author = User::factory()->create();
    $service = Service::factory()->create();

    $recipients = (new ResolveServiceDiscussionRecipients())($service, $author);

    expect($recipients)->toBeEmpty();
});
