<?php

namespace Tests\Feature;

use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Models\Template;
use App\Models\User;
use Livewire\Volt\Volt as LivewireVolt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated users can edit template elements', function (): void {
    $user = User::factory()->create();
    $template = Template::factory()->create(['name' => 'Test Template']);

    // Create a liturgy element for the template
    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Template::class,
        'liturgy_id' => $template->id,
        'type' => LiturgyElementType::SECTION,
        'name' => 'Original Name',
        'description' => 'Original Description',
        'order' => 1,
    ]);

    $this->actingAs($user);

    // Test editing the element
    LivewireVolt::test('templates.elements', ['templateId' => $template->id])
        ->call('editElement', $element->id)
        ->assertSet('elementForm.name', 'Original Name')
        ->assertSet('elementForm.description', 'Original Description')
        ->assertSet('elementForm.type', LiturgyElementType::SECTION->value)
        ->set('elementForm.name', 'Updated Name')
        ->set('elementForm.description', 'Updated Description')
        ->set('elementForm.type', LiturgyElementType::SONG->value)
        ->call('updateElement')
        ->assertHasNoErrors();

    // Verify the element was updated in the database
    $element->refresh();
    expect($element->name)->toBe('Updated Name');
    expect($element->description)->toBe('Updated Description');
    expect($element->type)->toBe(LiturgyElementType::SONG);
});

test('editing template elements validates required fields', function (): void {
    $user = User::factory()->create();
    $template = Template::factory()->create(['name' => 'Test Template']);

    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Template::class,
        'liturgy_id' => $template->id,
        'type' => LiturgyElementType::SECTION,
        'name' => 'Original Name',
        'order' => 1,
    ]);

    $this->actingAs($user);

    // Test validation by setting empty name
    LivewireVolt::test('templates.elements', ['templateId' => $template->id])
        ->call('editElement', $element->id)
        ->set('elementForm.name', '')
        ->call('updateElement')
        ->assertHasErrors(['elementForm.name']);
});

test('editing reading elements shows reading type field', function (): void {
    $user = User::factory()->create();
    $template = Template::factory()->create(['name' => 'Test Template']);

    $element = LiturgyElement::factory()->create([
        'liturgy_type' => Template::class,
        'liturgy_id' => $template->id,
        'type' => LiturgyElementType::SECTION,
        'name' => 'Original Name',
        'order' => 1,
    ]);

    $this->actingAs($user);

    // Test that changing to reading type shows reading_type field
    LivewireVolt::test('templates.elements', ['templateId' => $template->id])
        ->call('editElement', $element->id)
        ->set('elementForm.type', LiturgyElementType::READING->value)
        ->call('$refresh')
        ->assertSeeHtml('Reading Type');
});
