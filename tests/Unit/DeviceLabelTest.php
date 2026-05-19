<?php

declare(strict_types=1);

use App\Support\DeviceLabel;

it('returns "Unknown device" when the user agent is null', function (): void {
    expect(DeviceLabel::fromUserAgent(null))->toBe('Unknown device');
});

it('returns "Unknown device" when the user agent is an empty string', function (): void {
    expect(DeviceLabel::fromUserAgent(''))->toBe('Unknown device');
    expect(DeviceLabel::fromUserAgent('   '))->toBe('Unknown device');
});

it('parses a representative fixture table of user agents', function (string $userAgent, string $expected): void {
    expect(DeviceLabel::fromUserAgent($userAgent))->toBe($expected);
})->with([
    'Chrome on macOS' => [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Chrome on macOS',
    ],
    'Safari on iPhone' => [
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        'Safari on iOS',
    ],
    'Firefox on Windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
        'Firefox on Windows',
    ],
    'Edge on Android' => [
        'Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 EdgA/120.0.0.0',
        'Edge on Android',
    ],
    'Stave PWA standalone on iOS' => [
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
        'Browser on iOS',
    ],
    'Chrome on Windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Chrome on Windows',
    ],
    'Edge on Windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        'Edge on Windows',
    ],
    'Safari on iPad' => [
        'Mozilla/5.0 (iPad; CPU OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        'Safari on iPadOS',
    ],
    'Firefox on Linux' => [
        'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
        'Firefox on Linux',
    ],
    'Opera on macOS' => [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0',
        'Opera on macOS',
    ],
    'Completely unknown UA' => [
        'CustomBot/1.0 (compatible; some-crawler)',
        'Browser on device',
    ],
]);
