<?php

declare(strict_types=1);

use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated user can view discussion component', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->assertStatus(200);
});

test('authenticated user can submit a comment', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->set('comment', '<p>Test comment content</p>')
        ->call('saveComment')
        ->assertSet('comment', '');

    expect($service->fresh()->comments()->count())->toBe(1);
    expect($service->fresh()->comments()->first()->text)->toContain('Test comment content');
});

test('comment is associated with authenticated user', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->set('comment', '<p>My comment</p>')
        ->call('saveComment');

    $comment = $service->fresh()->comments()->first();
    expect($comment->commentator->id)->toBe($user->id);
});

test('user can react to a comment', function (): void {
    $user = User::factory()->create();
    $service = Service::factory()->create();
    $service->comment('<p>Initial comment</p>', $user);
    $comment = $service->comments()->first();

    $this->actingAs($user);

    Livewire::test('services.discussion', ['serviceId' => $service->id])
        ->call('react', $comment->id, '👍');

    expect($comment->fresh()->reactions()->count())->toBe(1);
});
