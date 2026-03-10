<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupMembershipResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
        public bool $approved,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Your request to join {$this->group->name} was {$this->status()}")
            ->line("Your request to join {$this->group->name} has been {$this->status()}.")
            ->when($this->approved, fn (MailMessage $mail) => $mail->action('View Group', route('groups.show', $this->group)));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'status' => $this->status(),
        ];
    }

    private function status(): string
    {
        return $this->approved ? 'approved' : 'rejected';
    }
}
