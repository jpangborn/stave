<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * @property-read string $gravatar
 */
trait HasGravatar
{
    public function gravatar(): Attribute
    {
        return Attribute::make(
            get: function () {
                $email = $this->email ?? '';
                $hash = hash('sha256', strtolower(trim($email)));

                return "https://www.gravatar.com/avatar/{$hash}";
            },
        );
    }

    /**
     * Resolve a Gravatar URL only when the user actually has one registered.
     * Returns null when the email has no Gravatar — pass it as `src` to
     * `flux:avatar` so it falls back to initials.
     *
     * Lookups are cached per email-hash for a week so we don't hit Gravatar
     * on every render.
     */
    public function gravatarUrl(int $size = 96): ?string
    {
        $email = trim($this->email ?? '');
        if ($email === '') {
            return null;
        }

        $hash = hash('sha256', strtolower($email));

        $exists = Cache::remember(
            "gravatar:exists:{$hash}",
            now()->addDays(7),
            function () use ($hash): bool {
                try {
                    return Http::timeout(2)
                        ->head("https://www.gravatar.com/avatar/{$hash}?d=404")
                        ->successful();
                } catch (Throwable) {
                    return false;
                }
            }
        );

        return $exists
            ? "https://www.gravatar.com/avatar/{$hash}?s={$size}"
            : null;
    }
}
