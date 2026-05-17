<?php

namespace App\CommentTransformers;

use Spatie\Comments\CommentTransformers\MentionsTransformer;
use Spatie\Comments\Models\Comment;

/**
 * Spatie's MentionsTransformer reads from $comment->text, assuming a prior
 * transformer (e.g. MarkdownToHtmlTransformer) already promoted original_text
 * into text. Stave stores comments as raw HTML from the Tiptap editor, so
 * we promote the original text ourselves before resolving mentions.
 */
class RawHtmlMentionsTransformer extends MentionsTransformer
{
    public function handle(Comment $comment): void
    {
        $comment->text = $comment->original_text;

        parent::handle($comment);
    }
}
