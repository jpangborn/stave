<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\DigestFrequency;
use App\Models\EmailDigestItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, EmailDigestItem>  $items
     */
    public function __construct(
        public User $user,
        public Collection $items,
        public DigestFrequency $frequency,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->items->count();

        return new Envelope(
            subject: "Your {$this->frequency->value} Stave digest — {$count} ".Str::plural('update', $count),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.notification-digest',
        );
    }
}
