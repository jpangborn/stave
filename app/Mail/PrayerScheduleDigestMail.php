<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrayerScheduleDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name: string, household: ?string, status: string, requests: array<int, string>}>  $people
     */
    public function __construct(
        public User $recipient,
        public array $people,
        public int $weekNumber,
        public int $totalWeeks,
        public string $weekRange,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Prayer rota — Week {$this->weekNumber} of {$this->totalWeeks} ({$this->weekRange})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.prayer-schedule-digest',
        );
    }
}
