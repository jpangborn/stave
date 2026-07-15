<?php

namespace App\Support;

use App\Models\Church;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Container-scoped resolver for the church the current request (or job,
 * command, test) is operating on. Resolution order: an explicitly set church,
 * then the authenticated user's current church, then null. When no church
 * resolves, church scoping is a no-op so migrations, seeders, and queued
 * model restoration keep working.
 */
class CurrentChurch
{
    private ?Church $church = null;

    public function set(?Church $church): void
    {
        $this->church = $church;
    }

    public function get(): ?Church
    {
        if ($this->church instanceof Church) {
            return $this->church;
        }

        $user = Auth::user();

        return $user instanceof User ? $user->currentChurch : null;
    }

    public function id(): ?int
    {
        if ($this->church instanceof Church) {
            return $this->church->id;
        }

        $user = Auth::user();

        return $user instanceof User ? $user->current_church_id : null;
    }

    /**
     * Run the callback with the given church as the current church, restoring
     * the previous state afterwards. Use for console commands and queued jobs
     * that query church-scoped models outside a request.
     *
     * @template TReturn
     *
     * @param  Closure(Church): TReturn  $callback
     * @return TReturn
     */
    public function runAs(Church $church, Closure $callback): mixed
    {
        $previous = $this->church;
        $this->set($church);

        try {
            return $callback($church);
        } finally {
            $this->set($previous);
        }
    }
}
