<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageReactionToggled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $authorId,
    ) {}

    /**
     * Broadcast to every other participant's private user channel so an open
     * thread can re-render reaction chips live. The reactor is excluded — their
     * own UI already updated via the Livewire round-trip.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return $this->conversation->group->members()
            ->where('users.id', '!=', $this->authorId)
            ->pluck('users.id')
            ->map(fn (int $id): PrivateChannel => new PrivateChannel("App.Models.User.{$id}"))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'reaction.toggled';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'is_direct' => true,
        ];
    }
}
