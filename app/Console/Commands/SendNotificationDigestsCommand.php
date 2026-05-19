<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DigestFrequency;
use App\Mail\NotificationDigestMail;
use App\Models\EmailDigestItem;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

#[Signature('stave:send-digests {--frequency=daily : Digest cadence to send (daily or weekly)}')]
#[Description('Send pending email-digest summaries to users whose digest_frequency matches the given cadence.')]
class SendNotificationDigestsCommand extends Command
{
    public function handle(): int
    {
        $frequency = DigestFrequency::tryFrom((string) $this->option('frequency'));

        if (! $frequency || $frequency === DigestFrequency::OFF) {
            $this->error('Frequency must be "daily" or "weekly".');

            return self::FAILURE;
        }

        $userCount = 0;
        $itemCount = 0;

        User::query()
            ->where('digest_frequency', $frequency->value)
            ->whereHas('pendingDigestItems')
            ->chunkById(100, function ($users) use ($frequency, &$userCount, &$itemCount): void {
                foreach ($users as $user) {
                    $sent = $this->sendDigestTo($user, $frequency);

                    if ($sent > 0) {
                        $userCount++;
                        $itemCount += $sent;
                    }
                }
            });

        $this->info("Sent {$frequency->value} digest to {$userCount} users covering {$itemCount} items.");

        return self::SUCCESS;
    }

    private function sendDigestTo(User $user, DigestFrequency $frequency): int
    {
        return DB::transaction(function () use ($user, $frequency): int {
            $items = $user->pendingDigestItems()
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                return 0;
            }

            Mail::to($user)->send(new NotificationDigestMail($user, $items, $frequency));

            EmailDigestItem::query()
                ->whereIn('id', $items->pluck('id'))
                ->update(['sent_at' => now()]);

            return $items->count();
        });
    }
}
