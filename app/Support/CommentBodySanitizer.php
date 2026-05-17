<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;
use Spatie\Comments\Support\CommentSanitizer;

class CommentBodySanitizer extends CommentSanitizer
{
    public function sanitize(string $text): string
    {
        return Purifier::clean($text, 'comment_body');
    }
}
