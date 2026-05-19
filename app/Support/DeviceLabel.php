<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Human-readable device label parsed from a User-Agent string.
 *
 * Intentionally tiny: we want a recognizable "Chrome on macOS" style label
 * for the manage-devices UI, not a full UA database. Unknown UAs degrade
 * to "Browser on device" so the row still renders.
 */
final class DeviceLabel
{
    public static function fromUserAgent(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'Unknown device';
        }

        return self::browser($userAgent).' on '.self::os($userAgent);
    }

    private static function browser(string $userAgent): string
    {
        // Order matters: Edge & Opera advertise "Chrome" too, so check them first.
        return match (true) {
            (bool) preg_match('/\b(Edg|Edge|EdgiOS|EdgA)\b/i', $userAgent) => 'Edge',
            (bool) preg_match('/\b(OPR|Opera)\b/i', $userAgent) => 'Opera',
            (bool) preg_match('/\bFirefox\b/i', $userAgent) => 'Firefox',
            (bool) preg_match('/\bChrome\b/i', $userAgent) => 'Chrome',
            (bool) preg_match('/\bSafari\b/i', $userAgent) => 'Safari',
            default => 'Browser',
        };
    }

    private static function os(string $userAgent): string
    {
        // iPadOS UAs sometimes look like macOS, so check iPad explicitly first.
        return match (true) {
            (bool) preg_match('/\biPad\b/i', $userAgent) => 'iPadOS',
            (bool) preg_match('/\b(iPhone|iPod)\b/i', $userAgent) => 'iOS',
            (bool) preg_match('/\bAndroid\b/i', $userAgent) => 'Android',
            (bool) preg_match('/\b(Windows NT|Windows)\b/i', $userAgent) => 'Windows',
            (bool) preg_match('/\bMac OS X\b/i', $userAgent) => 'macOS',
            (bool) preg_match('/\b(Linux|X11)\b/i', $userAgent) => 'Linux',
            default => 'device',
        };
    }
}
