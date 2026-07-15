<?php

namespace App\Http\Middleware;

use App\Models\Church;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees every authenticated request has a valid current church:
 * self-heals a stale current_church_id from the user's memberships and
 * routes users without any church to onboarding.
 */
class EnsureCurrentChurch
{
    /**
     * Routes a churchless user may still reach.
     *
     * @var array<int, string>
     */
    private array $allowedRoutes = [
        'churches.create',
        'churches.join',
        'invitations.accept',
        'logout',
        'settings.profile',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->current_church_id !== null
            && $user->churches()->whereKey($user->current_church_id)->exists()) {
            return $next($request);
        }

        $fallback = $user->churches()->orderBy('churches.id')->first();

        if ($fallback instanceof Church) {
            $user->forceFill(['current_church_id' => $fallback->id])->save();

            return $next($request);
        }

        if ($request->routeIs(...$this->allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('churches.create');
    }
}
