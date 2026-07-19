<?php

use App\Enums\MembershipStatus;
use App\Mail\PrayerScheduleDigestMail;
use App\Models\Church;
use App\Models\PastoralNote;
use App\Models\Person;
use App\Models\PersonOffice;
use App\Models\PrayerRequest;
use App\Models\PrayerScheduleSettings;
use App\Models\User;
use App\Services\PrayerScheduleService;
use App\Support\CurrentChurch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Collapse the cycle to a single week so everyone is scheduled in the current
 * week, making the command's output deterministic.
 */
function updateSchedule(array $attributes): void
{
    $church = Church::query()->orderBy('id')->first() ?? Church::factory()->create();

    app(CurrentChurch::class)->runAs(
        $church,
        fn () => PrayerScheduleSettings::current()->update($attributes),
    );
}

function singleWeekSchedule(): void
{
    updateSchedule([
        'cycle_weeks' => 1,
        'anchor_date' => Carbon::now()->startOfWeek(Carbon::MONDAY),
        'include_statuses' => [MembershipStatus::MEMBER->value, MembershipStatus::CATECHUMEN->value],
    ]);
}

function makeElder(): User
{
    $person = Person::factory()->member()->create();
    PersonOffice::factory()->elder()->for($person)->create();

    return User::factory()->create(['person_id' => $person->id]);
}

test('the email is queued to elders with accounts but not to non-elders', function (): void {
    Mail::fake();
    singleWeekSchedule();

    $elder = makeElder();
    $nonElder = User::factory()->create();
    Person::factory()->member()->create();

    $this->artisan('stave:send-prayer-schedule')->assertSuccessful();

    Mail::assertQueued(PrayerScheduleDigestMail::class, fn (PrayerScheduleDigestMail $mail): bool => $mail->hasTo($elder->email));
    Mail::assertNotQueued(PrayerScheduleDigestMail::class, fn (PrayerScheduleDigestMail $mail): bool => $mail->hasTo($nonElder->email));
});

test('the email contains only open bulletin requests', function (): void {
    Mail::fake();
    singleWeekSchedule();
    makeElder();

    $subject = Person::factory()->member()->create();
    PrayerRequest::factory()->open()->bulletin()->for($subject)->create(['body' => 'Open bulletin request']);
    PrayerRequest::factory()->open()->private()->for($subject)->create(['body' => 'Secret private request']);
    PrayerRequest::factory()->bulletin()->completed()->for($subject)->create(['body' => 'Already answered request']);
    PastoralNote::factory()->for($subject)->create(['body' => 'Confidential pastoral note']);

    $this->artisan('stave:send-prayer-schedule')->assertSuccessful();

    Mail::assertQueued(PrayerScheduleDigestMail::class, function (PrayerScheduleDigestMail $mail): bool {
        $bodies = collect($mail->people)->flatMap(fn (array $person): array => $person['requests']);

        return $bodies->contains('Open bulletin request')
            && ! $bodies->contains('Secret private request')
            && ! $bodies->contains('Already answered request')
            && ! $bodies->contains('Confidential pastoral note');
    });
});

test('the rendered email never leaks private content', function (): void {
    singleWeekSchedule();
    $elder = makeElder();

    $subject = Person::factory()->member()->create();
    PrayerRequest::factory()->open()->bulletin()->for($subject)->create(['body' => 'Pray for the bulletin item']);
    PrayerRequest::factory()->open()->private()->for($subject)->create(['body' => 'Hidden private item']);

    $service = app(PrayerScheduleService::class);
    $settings = PrayerScheduleSettings::current();
    $mail = new PrayerScheduleDigestMail(
        recipient: $elder,
        people: $service->bulletinDigestForWeek($settings, 0),
        weekNumber: 1,
        totalWeeks: 1,
        weekRange: $service->weekRange($settings, 0),
    );

    $rendered = $mail->render();

    expect($rendered)->toContain('Pray for the bulletin item')
        ->and($rendered)->not->toContain('Hidden private item');
});

test('elders without a user account are skipped', function (): void {
    Mail::fake();
    singleWeekSchedule();

    makeElder();
    $accountlessElder = Person::factory()->member()->create();
    PersonOffice::factory()->elder()->for($accountlessElder)->create();

    $this->artisan('stave:send-prayer-schedule')
        ->expectsOutputToContain('1 elder(s) have no user account')
        ->assertSuccessful();

    Mail::assertQueuedCount(1);
});

test('nothing is sent when no one is scheduled for the week', function (): void {
    Mail::fake();
    singleWeekSchedule();
    makeElder();

    // Exclude every status so the rotation is empty.
    updateSchedule(['include_statuses' => []]);

    $this->artisan('stave:send-prayer-schedule')
        ->expectsOutputToContain('nothing to send')
        ->assertSuccessful();

    Mail::assertNothingQueued();
});
