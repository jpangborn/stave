<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use Illuminate\Support\Str;

trait HasCommentPreview
{
    protected function commentPreview(string $html, int $length = 120): string
    {
        $plain = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));

        return Str::limit($plain, $length);
    }
}
