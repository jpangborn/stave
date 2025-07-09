<?php

use App\Models\Template;
use App\Models\User;
use Livewire\Volt\Volt as LivewireVolt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** @group templates */
test('guests are redirected from the templates index', function (): void {
    $response = $this->get('/templates');
    $response->assertRedirect('/login');
});

test('authenticated users can view the templates index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/templates')
        ->assertStatus(200);
});

test('guests are redirected from the template create page', function (): void {
    $response = $this->get('/templates/create');
    $response->assertRedirect('/login');
});

test('authenticated users can view the template create page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/templates/create')
        ->assertStatus(200)
        ->assertSee('Add a Template');
});

test('template name is required when creating a template', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('templates.create')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

test('authenticated users can create a template', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    LivewireVolt::test('templates.create')
        ->set('form.name', 'Test Template')
        ->set('form.default', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/templates');

    $this->assertDatabaseHas('templates', ['name' => 'Test Template']);
});

test('guests are redirected from the template show page', function (): void {
    $template = Template::create([
        'name' => 'Test Template',
        'default' => false,
    ]);
    $response = $this->get("/templates/{$template->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view the template show page', function (): void {
    $user = User::factory()->create();
    $template = Template::create([
        'name' => 'Test Template',
        'default' => false,
    ]);

    $this->actingAs($user)
        ->get("/templates/{$template->id}")
        ->assertStatus(200)
        ->assertSee('Test Template');
});

test('guests are redirected from the template edit page', function (): void {
    $template = Template::create([
        'name' => 'Edit Me',
        'default' => true,
    ]);
    $response = $this->get("/templates/{$template->id}/edit");
    $response->assertRedirect('/login');
});

test('authenticated users can view the template edit page', function (): void {
    $user = User::factory()->create();
    $template = Template::create([
        'name' => 'Edit Me',
        'default' => true,
    ]);

    $this->actingAs($user)
        ->get("/templates/{$template->id}/edit")
        ->assertStatus(200)
        ->assertSee('Template Details')
        ->assertSee('Edit Me');
});

test('authenticated users can update a template', function (): void {
    $user = User::factory()->create();
    $template = Template::create([
        'name' => 'Old Name',
        'default' => false,
    ]);

    $this->actingAs($user);

    LivewireVolt::test('templates.edit', ['template' => $template->id])
        ->set('form.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/templates');

    expect($template->fresh()->name)->toBe('New Name');
});

test('authenticated users can delete a template', function (): void {
    $user = User::factory()->create();
    $template = Template::create([
        'name' => 'Delete Me',
        'default' => false,
    ]);

    $this->actingAs($user);

    LivewireVolt::test('templates.edit', ['template' => $template->id])
        ->call('delete')
        ->assertRedirect('/templates');

    $this->assertDatabaseMissing('templates', ['id' => $template->id]);
});
