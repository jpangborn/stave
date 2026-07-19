<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Office;
use App\Mail\PrayerScheduleDigestMail;
use App\Models\Church;
use App\Models\Person;
use App\Models\PrayerScheduleSettings;
use App\Models\User;
use App\Services\PrayerScheduleService;
use App\Support\CurrentChurch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

#[Signature('stave:send-prayer-schedule {--week= : Zero-based week index to send (defaults to the current week)}')]
#[Description('Email the elders the prayer rota for the week, including only open bulletin-visibility requests.')]
class SendPrayerScheduleDigestCommand extends Command
{
    public function handle(PrayerScheduleService $service): int
    {
        Church::query()->orderBy('id')->each(
            fn (Church $church) => app(CurrentChurch::class)->runAs(
                $church,
                fn () => $this->sendForChurch($church, $service),
            ),
        );

        return self::SUCCESS;
    }

    private function sendForChurch(Church $church, PrayerScheduleService $service): void
    {
        $settings = PrayerScheduleSettings::current();

        $week = $this->option('week') !== null
            ? (int) $this->option('week')
            : $service->currentWeekIndex($settings);

        $people = $service->bulletinDigestForWeek($settings, $week);

        if ($people === []) {
            $this->info("{$church->name}: no one is scheduled for this week — nothing to send.");

            return;
        }

        /** @var Collection<int, User> $elders */
        $elders = User::query()
            ->whereHas('person.offices', fn ($query) => $query->where('kind', Office::ELDER))
            ->get();

        $eldersWithoutAccounts = Person::query()
            ->whereHas('offices', fn ($query) => $query->where('kind', Office::ELDER))
            ->whereDoesntHave('user')
            ->count();

        foreach ($elders as $elder) {
            Mail::to($elder)->send(new PrayerScheduleDigestMail(
                recipient: $elder,
                people: $people,
                weekNumber: $week + 1,
                totalWeeks: $settings->cycle_weeks,
                weekRange: $service->weekRange($settings, $week),
            ));
        }

        $weekNumber = $week + 1;
        $this->info("{$church->name}: sent the Week {$weekNumber} prayer rota to {$elders->count()} elders.");

        if ($eldersWithoutAccounts > 0) {
            $this->warn("{$church->name}: {$eldersWithoutAccounts} elder(s) have no user account and were not emailed.");
        }
    }
}
