<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\ConversationFile;
use App\Models\User;

class ConversationFilePolicy
{
    public function __construct(private ConversationPolicy $conversations) {}

    public function view(User $user, ConversationFile $file): bool
    {
        return $this->conversations->view($user, $file->conversation);
    }

    public function create(User $user, Conversation $conversation): bool
    {
        return $this->conversations->comment($user, $conversation);
    }

    public function delete(User $user, ConversationFile $file): bool
    {
        return $file->uploader_id === $user->id
            || $file->conversation->group->hasLeader($user);
    }
}
