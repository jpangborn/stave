<?php

declare(strict_types=1);

namespace App\Recipients;

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;

class ResolveServiceDiscussionRecipients
{
    /** @return Collection<int, User> */
    public function __invoke(Service $service, User $author): Collection
    {
        return $service->assignedUsers()
            ->reject(fn (User $user): bool => $user->id === $author->id)
            ->unique('id')
            ->values();
    }
}
