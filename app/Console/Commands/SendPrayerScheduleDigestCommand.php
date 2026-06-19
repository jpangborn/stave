<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Office;
use App\Mail\PrayerScheduleDigestMail;
use App\Models\Person;
use App\Models\PrayerScheduleSettings;
use App\Models\User;
use App\Services\PrayerScheduleService;
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
        $settings = PrayerScheduleSettings::current();

        $week = $this->option('week') !== null
            ? (int) $this->option('week')
            : $service->currentWeekIndex($settings);

        $people = $service->bulletinDigestForWeek($settings, $week);

        if ($people === []) {
            $this->info('No one is scheduled for this week — nothing to send.');

            return self::SUCCESS;
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
        $this->info("Sent the Week {$weekNumber} prayer rota to {$elders->count()} elders.");

        if ($eldersWithoutAccounts > 0) {
            $this->warn("{$eldersWithoutAccounts} elder(s) have no user account and were not emailed.");
        }

        return self::SUCCESS;
    }
}
