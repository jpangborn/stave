<?php

use App\Enums\AccessRole;
use App\Models\Person;
use App\Models\PrayerScheduleSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function schedulePlanner(): User
{
    $user = User::factory()->create();
    $user->grantAccessRole(AccessRole::PASTORAL_CARE_USER);

    return $user;
}

test('non pastoral-care users cannot access the schedule', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('prayer-schedule.index'))
        ->assertForbidden();
});

test('pastoral-care users can view the schedule', function (): void {
    $this->actingAs(schedulePlanner())
        ->get(route('prayer-schedule.index'))
        ->assertOk()
        ->assertSee('Prayer Schedule');
});

test('toggling a status chip updates and persists the included statuses', function (): void {
    Livewire::actingAs(schedulePlanner())
        ->test('pages::prayer-schedule.index')
        ->assertSet('includeStatuses', ['member', 'catechumen'])
        ->call('toggleStatus', 'catechumen')
        ->assertSet('includeStatuses', ['member'])
        ->call('toggleStatus', 'adherent')
        ->assertSet('includeStatuses', ['member', 'adherent']);

    expect(PrayerScheduleSettings::current()->include_statuses)->toBe(['member', 'adherent']);
});

test('changing the cycle length persists and clamps to 4-13 weeks', function (): void {
    $component = Livewire::actingAs(schedulePlanner())
        ->test('pages::prayer-schedule.index')
        ->call('incrementWeeks');

    expect(PrayerScheduleSettings::current()->cycle_weeks)->toBe(9);

    foreach (range(1, 10) as $ignored) {
        $component->call('decrementWeeks');
    }

    $component->assertSet('cycleWeeks', 4);
    expect(PrayerScheduleSettings::current()->cycle_weeks)->toBe(4);
});

test('switching between weeks and roster views', function (): void {
    Livewire::actingAs(schedulePlanner())
        ->test('pages::prayer-schedule.index')
        ->assertSet('view', 'weeks')
        ->set('view', 'roster')
        ->assertSet('view', 'roster')
        ->assertSee('Prayer week');
});

test('the rotation only includes the selected statuses', function (): void {
    $member = Person::factory()->member()->create(['last_name' => 'Aaa']);
    $catechumen = Person::factory()->catechumen()->create(['last_name' => 'Bbb']);
    $adherent = Person::factory()->adherent()->create(['last_name' => 'Ccc']);

    $component = Livewire::actingAs(schedulePlanner())->test('pages::prayer-schedule.index');

    $ids = $component->instance()->buckets->flatten(1)->pluck('id');

    expect($ids)->toContain($member->id)
        ->toContain($catechumen->id)
        ->not->toContain($adherent->id);

    $component->call('toggleStatus', 'adherent');
    $ids = $component->instance()->buckets->flatten(1)->pluck('id');

    expect($ids)->toContain($adherent->id);
});
