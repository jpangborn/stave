<?php

use App\Enums\LiturgyElementType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @group browser */
it('renders the service discussion tab full height without smoke', function (): void {
    $user = User::factory()->create(['name' => 'Smoke Tester']);

    $service = Service::factory()->create(['title' => 'Sunday Morning Service']);

    // An assigned element makes the user a discussion participant.
    $service->liturgyElements()->create([
        'type' => LiturgyElementType::SERMON,
        'name' => 'Sermon',
        'assignee_id' => $user->id,
        'order' => 0,
    ]);

    $service->comment('<p>Are you preaching this week?</p>', $user);
    $service->comment('<p>Yes — Romans 8:31.</p>', $user);

    $this->actingAs($user);

    $page = visit(route('services.show', ['service' => $service, 'tab' => 'discussion']));

    $page->assertSee('Sunday Morning Service')
        ->assertSee('Romans 8:31')
        ->assertSee('In this discussion')
        ->assertNoJavascriptErrors()
        ->assertNoSmoke();
});
