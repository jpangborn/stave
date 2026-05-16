<?php

namespace App\Services;

use DOMDocument;
use DOMText;
use DOMXPath;

class ScriptureLinker
{
    /**
     * Matches "Romans 8:31", "John 1:14", "1 Corinthians 13:4", "Romans 8:31-39", "Romans 8:31–39".
     *
     * Group 1: optional numeric prefix (1, 2, 3, I, II, III)
     * Group 2: book name (single title-cased word — every canonical book is a single token)
     * Group 3: chapter
     * Group 4: verse or verse range
     *
     * Restricting the book to one token prevents an adjacent capitalized word
     * ("See Romans 8:31") from being swept into the match.
     */
    private const SCRIPTURE_REGEX = '/\b((?:1|2|3|I|II|III)\s+)?([A-Z][a-z]+)\.?\s(\d+):(\d+(?:[\x{2013}-]\d+)?)\b/u';

    /** @var array<int, string> Canonical book names. The captured book token must appear here for the ref to link. */
    private const CANONICAL_BOOKS = [
        'Genesis', 'Exodus', 'Leviticus', 'Numbers', 'Deuteronomy', 'Joshua', 'Judges', 'Ruth',
        'Samuel', 'Kings', 'Chronicles', 'Ezra', 'Nehemiah', 'Esther', 'Job', 'Psalms', 'Psalm',
        'Proverbs', 'Ecclesiastes', 'Song', 'Isaiah', 'Jeremiah', 'Lamentations', 'Ezekiel',
        'Daniel', 'Hosea', 'Joel', 'Amos', 'Obadiah', 'Jonah', 'Micah', 'Nahum', 'Habakkuk',
        'Zephaniah', 'Haggai', 'Zechariah', 'Malachi', 'Matthew', 'Mark', 'Luke', 'John', 'Acts',
        'Romans', 'Corinthians', 'Galatians', 'Ephesians', 'Philippians', 'Colossians',
        'Thessalonians', 'Timothy', 'Titus', 'Philemon', 'Hebrews', 'James', 'Peter', 'Jude',
        'Revelation',
    ];

    /** @var array<string, string> Abbreviation → canonical book token. */
    private const ABBREVIATIONS = [
        'Gen' => 'Genesis', 'Exod' => 'Exodus', 'Lev' => 'Leviticus', 'Num' => 'Numbers',
        'Deut' => 'Deuteronomy', 'Josh' => 'Joshua', 'Judg' => 'Judges',
        'Sam' => 'Samuel', 'Kgs' => 'Kings', 'Chr' => 'Chronicles',
        'Neh' => 'Nehemiah', 'Esth' => 'Esther',
        'Ps' => 'Psalms', 'Pss' => 'Psalms',
        'Prov' => 'Proverbs', 'Eccl' => 'Ecclesiastes',
        'Isa' => 'Isaiah', 'Jer' => 'Jeremiah', 'Lam' => 'Lamentations', 'Ezek' => 'Ezekiel',
        'Dan' => 'Daniel', 'Hos' => 'Hosea', 'Obad' => 'Obadiah', 'Mic' => 'Micah',
        'Nah' => 'Nahum', 'Hab' => 'Habakkuk', 'Zeph' => 'Zephaniah', 'Hag' => 'Haggai',
        'Zech' => 'Zechariah', 'Mal' => 'Malachi',
        'Matt' => 'Matthew', 'Mt' => 'Matthew', 'Mk' => 'Mark', 'Lk' => 'Luke', 'Jn' => 'John',
        'Rom' => 'Romans', 'Cor' => 'Corinthians', 'Gal' => 'Galatians', 'Eph' => 'Ephesians',
        'Phil' => 'Philippians', 'Phlm' => 'Philemon', 'Col' => 'Colossians',
        'Thess' => 'Thessalonians', 'Tim' => 'Timothy',
        'Heb' => 'Hebrews', 'Jas' => 'James', 'Pet' => 'Peter', 'Rev' => 'Revelation',
    ];

    /** @var array<string, string> Overrides for book tokens whose MereBible slug differs from a simple lowercase. */
    private const BOOK_SLUGS = [
        'Psalm' => 'psalms',
        'Psalms' => 'psalms',
    ];

    private const MEREBIBLE_BASE_URL = 'https://www.merebible.app/read/csb';

    public function linkify(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // Wrap in a synthetic root so the input is treated as a fragment and not auto-wrapped in <html>/<body>.
        $dom->loadHTML(
            '<?xml encoding="UTF-8"?><div data-scripture-root>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()[not(ancestor::a)]');

        if ($textNodes !== false) {
            foreach ($textNodes as $textNode) {
                /** @var DOMText $textNode */
                $this->linkifyTextNode($dom, $textNode);
            }
        }

        $root = $dom->getElementsByTagName('div')->item(0);
        if ($root === null) {
            return $html;
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
    }

    private function linkifyTextNode(DOMDocument $dom, DOMText $textNode): void
    {
        $text = $textNode->nodeValue ?? '';

        if (preg_match_all(self::SCRIPTURE_REGEX, $text, $matches, PREG_OFFSET_CAPTURE) === false) {
            return;
        }

        if ($matches[0] === []) {
            return;
        }

        $fragment = $dom->createDocumentFragment();
        $cursor = 0;
        $emitted = false;

        foreach ($matches[0] as $i => [$full, $offset]) {
            $token = $matches[2][$i][0];
            $book = self::ABBREVIATIONS[$token] ?? $token;

            if (! in_array($book, self::CANONICAL_BOOKS, true)) {
                continue;
            }

            if ($offset > $cursor) {
                $fragment->appendChild($dom->createTextNode(substr($text, $cursor, $offset - $cursor)));
            }

            $anchor = $dom->createElement('a');
            $anchor->setAttribute('class', 'scripture-ref');
            $anchor->setAttribute('href', $this->buildUrl(
                $matches[1][$i][0],
                $book,
                $matches[3][$i][0],
                $matches[4][$i][0],
            ));
            $anchor->setAttribute('target', '_blank');
            $anchor->setAttribute('rel', 'noopener');
            $anchor->appendChild($dom->createTextNode($full));
            $fragment->appendChild($anchor);

            $cursor = $offset + strlen($full);
            $emitted = true;
        }

        if (! $emitted) {
            return;
        }

        if ($cursor < strlen($text)) {
            $fragment->appendChild($dom->createTextNode(substr($text, $cursor)));
        }

        $textNode->parentNode?->replaceChild($fragment, $textNode);
    }

    private function buildUrl(string $prefix, string $book, string $chapter, string $verses): string
    {
        $slug = self::BOOK_SLUGS[$book] ?? strtolower($book);

        $trimmedPrefix = trim($prefix);
        if ($trimmedPrefix !== '') {
            $arabic = match ($trimmedPrefix) {
                'I' => '1',
                'II' => '2',
                'III' => '3',
                default => $trimmedPrefix,
            };
            $slug = $arabic.'-'.$slug;
        }

        $parts = preg_split('/[\x{2013}\-]/u', $verses, 2);
        $verse = $parts[0];
        $endVerse = $parts[1] ?? null;

        $url = self::MEREBIBLE_BASE_URL."/{$slug}/{$chapter}?verse={$verse}";
        if ($endVerse !== null) {
            $url .= "&endVerse={$endVerse}";
        }

        return $url;
    }
}
