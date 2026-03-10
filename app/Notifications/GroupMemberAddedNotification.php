<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
        public User $addedBy,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("You've been added to {$this->group->name}")
            ->line("{$this->addedBy->name} added you to {$this->group->name}.")
            ->action('View Group', route('groups.show', $this->group));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'added_by_id' => $this->addedBy->id,
            'added_by_name' => $this->addedBy->name,
        ];
    }
}
