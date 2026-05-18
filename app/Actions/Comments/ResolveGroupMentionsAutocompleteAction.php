<?php

namespace App\Actions\Comments;

use App\Models\Conversation;
use App\Models\GroupUser;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Comments\Actions\ResolveMentionsAutocompleteAction;
use Spatie\Comments\Models\Concerns\Interfaces\CanComment;
use Spatie\Comments\Support\Config;

class ResolveGroupMentionsAutocompleteAction extends ResolveMentionsAutocompleteAction
{
    /**
     * @return array<int, CanComment>
     */
    public function execute(string $query, $commentable): array
    {
        if ($commentable instanceof Conversation) {
            return $this->resolveForConversation($query, $commentable);
        }

        if ($commentable instanceof Service) {
            return $this->resolveForService($query, $commentable);
        }

        return parent::execute($query, $commentable);
    }

    /**
     * @return array<int, CanComment>
     */
    private function resolveForConversation(string $query, Conversation $conversation): array
    {
        $nameField = Config::commentatorModelNameField();
        $currentUserId = Auth::id();

        $inThreadIds = $conversation->comments()
            ->where('commentator_type', (new User())->getMorphClass())
            ->whereNot('commentator_id', $currentUserId)
            ->distinct()
            ->pluck('commentator_id');

        /** @var \Closure(): BelongsToMany<User, Conversation, GroupUser> $base */
        $base = fn (): BelongsToMany => $conversation->group->members()
            ->where("users.{$nameField}", 'like', "%{$query}%")
            ->whereNot('users.id', $currentUserId)
            ->orderBy("users.{$nameField}");

        $inThreadMatches = $base()
            ->whereIn('users.id', $inThreadIds)
            ->limit(10)
            ->get();

        $otherMatches = $base()
            ->whereNotIn('users.id', $inThreadMatches->modelKeys())
            ->limit(10 - $inThreadMatches->count())
            ->get();

        /** @var array<int, CanComment> */
        return $inThreadMatches->merge($otherMatches)->values()->all();
    }

    /**
     * Service mentions are scoped to liturgy-element assignees.
     * Future: expand to all users with service-planning access.
     *
     * @return array<int, CanComment>
     */
    private function resolveForService(string $query, Service $service): array
    {
        $currentUserId = Auth::id();
        $needle = mb_strtolower($query);

        /** @var array<int, CanComment> */
        return $service->assignedUsers()
            ->filter(fn (User $user): bool => $user->id !== $currentUserId)
            ->filter(fn (User $user): bool => $needle === '' || str_contains(mb_strtolower((string) $user->name), $needle))
            ->sortBy('name')
            ->take(10)
            ->values()
            ->all();
    }
}
