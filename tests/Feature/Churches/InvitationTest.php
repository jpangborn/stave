<?php

use App\Enums\AccessRole;
use App\Mail\ChurchInvitationMail;
use App\Models\Church;
use App\Models\ChurchInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('only church admins can view the invitations page', function (): void {
    $church = Church::factory()->create();
    $member = User::factory()->forChurch($church)->create();
    $admin = User::factory()->forChurch($church)->create();
    $admin->grantAccessRole(AccessRole::ADMIN, $church);

    $this->actingAs($member)->get('/settings/church/invitations')->assertForbidden();
    $this->actingAs($admin)->get('/settings/church/invitations')->assertOk();
});

test('an admin can send an invitation with roles', function (): void {
    Mail::fake();

    $church = Church::factory()->create();
    $admin = User::factory()->forChurch($church)->create();
    $admin->grantAccessRole(AccessRole::ADMIN, $church);

    Livewire::actingAs($admin)
        ->test('pages::settings.church-invitations')
        ->set('email', 'invitee@example.com')
        ->set('roles', [AccessRole::LITURGY_ADMIN->value, AccessRole::PASTORAL_CARE_USER->value])
        ->call('invite')
        ->assertHasNoErrors();

    $invitation = ChurchInvitation::query()->sole();

    expect($invitation->church_id)->toBe($church->id)
        ->and($invitation->email)->toBe('invitee@example.com')
        ->and($invitation->roles)->toBe([AccessRole::LITURGY_ADMIN->value, AccessRole::PASTORAL_CARE_USER->value]);

    Mail::assertQueued(ChurchInvitationMail::class, fn (ChurchInvitationMail $mail): bool => $mail->hasTo('invitee@example.com'));
});

test('existing members cannot be invited again', function (): void {
    Mail::fake();

    $church = Church::factory()->create();
    $admin = User::factory()->forChurch($church)->create();
    $admin->grantAccessRole(AccessRole::ADMIN, $church);
    $member = User::factory()->forChurch($church)->create();

    Livewire::actingAs($admin)
        ->test('pages::settings.church-invitations')
        ->set('email', $member->email)
        ->call('invite')
        ->assertHasErrors('email');

    Mail::assertNothingQueued();
});

test('a logged-in user with a matching email can accept', function (): void {
    $church = Church::factory()->create();
    $invitee = User::factory()->create();

    $invitation = ChurchInvitation::factory()->create([
        'church_id' => $church->id,
        'email' => $invitee->email,
        'roles' => [AccessRole::LITURGY_USER->value],
    ]);

    Livewire::actingAs($invitee)
        ->test('pages::invitations.accept', ['token' => $invitation->token])
        ->call('join')
        ->assertRedirect(route('dashboard'));

    $invitee->refresh();

    expect($church->hasMember($invitee))->toBeTrue()
        ->and($invitee->current_church_id)->toBe($church->id)
        ->and($invitee->hasAccessRole(AccessRole::LITURGY_USER, $church))->toBeTrue()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('a logged-in user with a different email cannot accept', function (): void {
    $church = Church::factory()->create();
    $otherChurch = Church::factory()->create();
    $bystander = User::factory()->forChurch($otherChurch)->create();

    $invitation = ChurchInvitation::factory()->create([
        'church_id' => $church->id,
        'email' => 'someone-else@example.com',
    ]);

    Livewire::actingAs($bystander)
        ->test('pages::invitations.accept', ['token' => $invitation->token])
        ->call('join')
        ->assertForbidden();

    expect($church->hasMember($bystander))->toBeFalse();
});

test('a new user can register through an invitation and skips email verification', function (): void {
    $church = Church::factory()->create();

    $invitation = ChurchInvitation::factory()->create([
        'church_id' => $church->id,
        'email' => 'fresh@example.com',
        'roles' => [AccessRole::PASTORAL_CARE_ADMIN->value],
    ]);

    Livewire::test('pages::invitations.accept', ['token' => $invitation->token])
        ->set('name', 'Fresh Face')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'fresh@example.com')->sole();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($church->hasMember($user))->toBeTrue()
        ->and($user->current_church_id)->toBe($church->id)
        ->and($user->hasAccessRole(AccessRole::PASTORAL_CARE_ADMIN, $church))->toBeTrue()
        ->and($user->personFor($church))->not->toBeNull();

    $this->assertAuthenticated();
});

test('expired and accepted invitations 404', function (): void {
    $expired = ChurchInvitation::factory()->expired()->create();
    $accepted = ChurchInvitation::factory()->accepted()->create();

    $this->get(route('invitations.accept', $expired->token))->assertNotFound();
    $this->get(route('invitations.accept', $accepted->token))->assertNotFound();
    $this->get(route('invitations.accept', 'nonsense-token'))->assertNotFound();
});

test('an admin can revoke and resend invitations', function (): void {
    Mail::fake();

    $church = Church::factory()->create();
    $admin = User::factory()->forChurch($church)->create();
    $admin->grantAccessRole(AccessRole::ADMIN, $church);

    $invitation = ChurchInvitation::factory()->create([
        'church_id' => $church->id,
        'invited_by' => $admin->id,
    ]);
    $originalToken = $invitation->token;

    Livewire::actingAs($admin)
        ->test('pages::settings.church-invitations')
        ->call('resend', $invitation->id);

    expect($invitation->fresh()->token)->not->toBe($originalToken);
    Mail::assertQueued(ChurchInvitationMail::class);

    Livewire::actingAs($admin)
        ->test('pages::settings.church-invitations')
        ->call('revoke', $invitation->id);

    $this->assertDatabaseMissing('church_invitations', ['id' => $invitation->id]);
});
