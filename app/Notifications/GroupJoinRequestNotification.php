<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupJoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
        public User $requester,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("New join request for {$this->group->name}")
            ->line("{$this->requester->name} has requested to join {$this->group->name}.")
            ->action('View Group', route('groups.show', ['group' => $this->group, 'tab' => 'members']));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
        ];
    }
}
