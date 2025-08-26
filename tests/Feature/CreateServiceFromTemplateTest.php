<?php

use App\Livewire\Actions\CreateServiceFromTemplate;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Template;
use Illuminate\Support\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a service from a template with liturgy elements', function () {
    $template = Template::factory()
        ->has(LiturgyElement::factory()->withAssignee()->count(3))
        ->create();

    // Act: run the action (invokable class)
    (new CreateServiceFromTemplate())($template, Carbon::tomorrow());

    // Assert: a service was created
    $this->assertDatabaseCount('services', 1);

    $service = Service::first();
    expect($service->template_id)->toBe($template->id);

    // Assert: service has the same number of elements
    $this->assertDatabaseCount('liturgy_elements', $template->liturgyElements->count() * 2);

    // Check field-by-field equality
    foreach ($template->liturgyElements as $templateElement) {
        $this->assertDatabaseHas('liturgy_elements', [
            'liturgy_type' => Service::class,
            'liturgy_id' => $service->id,
            'type' => $templateElement->type,
            'order' => $templateElement->order,
            'assignee_id' => $templateElement->assignee_id,
            'content_id' => $templateElement->content_id,
            'content_type' => $templateElement->content_type,
        ]);
    }
});
