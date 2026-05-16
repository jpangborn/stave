<?php

use App\Services\ScriptureLinker;

function linkify(string $html): string
{
    return (new ScriptureLinker())->linkify($html);
}

test('wraps a canonical single-chapter reference', function (): void {
    $result = linkify('<p>See Romans 8:31 for the closer.</p>');

    expect($result)->toContain('<a class="scripture-ref"')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/romans/8?verse=31"')
        ->and($result)->toContain('target="_blank"')
        ->and($result)->toContain('rel="noopener"')
        ->and($result)->toContain('>Romans 8:31</a>')
        ->and($result)->toContain('See ')
        ->and($result)->toContain(' for the closer.');
});

test('wraps a reference with a numeric prefix', function (): void {
    $result = linkify('<p>1 Corinthians 13:4 sets the tone.</p>');

    expect($result)->toContain('>1 Corinthians 13:4</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/1-corinthians/13?verse=4"');
});

test('builds a range URL for a hyphenated verse range', function (): void {
    $result = linkify('<p>Romans 8:31-39 is the arc.</p>');

    expect($result)->toContain('href="https://www.merebible.app/read/csb/romans/8?verse=31&amp;endVerse=39"');
});

test('builds a range URL for an en-dash verse range', function (): void {
    $result = linkify('<p>Romans 8:31–39 is the arc.</p>');

    expect($result)->toContain('href="https://www.merebible.app/read/csb/romans/8?verse=31&amp;endVerse=39"');
});

test('maps the singular Psalm form to the plural psalms slug', function (): void {
    $result = linkify('<p>Psalm 23:1 echoes the same.</p>');

    expect($result)->toContain('href="https://www.merebible.app/read/csb/psalms/23?verse=1"');
});

test('wraps a verse range with a hyphen', function (): void {
    $result = linkify('<p>Romans 8:31-39 is the arc.</p>');

    expect($result)->toContain('>Romans 8:31-39</a>');
});

test('wraps a verse range with an en-dash', function (): void {
    $result = linkify('<p>Romans 8:31–39 is the arc.</p>');

    expect($result)->toContain('>Romans 8:31–39</a>');
});

test('wraps multiple references in the same body', function (): void {
    $result = linkify('<p>Romans 8:31 and John 1:14 both fit.</p>');

    expect($result)->toContain('>Romans 8:31</a>')
        ->and($result)->toContain('>John 1:14</a>')
        ->and(substr_count($result, 'class="scripture-ref"'))->toBe(2);
});

test('skips non-canonical book names', function (): void {
    $result = linkify('<p>Wonderful 12:34 should not link.</p>');

    expect($result)->not->toContain('scripture-ref');
});

test('skips a reference that already lives inside an anchor', function (): void {
    $input = '<p>See <a href="https://example.com">Romans 8:31</a> for more.</p>';
    $result = linkify($input);

    expect(substr_count($result, 'class="scripture-ref"'))->toBe(0)
        ->and($result)->toContain('<a href="https://example.com">Romans 8:31</a>');
});

test('still links references that sit outside an unrelated anchor', function (): void {
    $input = '<p>See <a href="https://example.com">resource</a> and Romans 8:31 below.</p>';
    $result = linkify($input);

    expect(substr_count($result, 'class="scripture-ref"'))->toBe(1)
        ->and($result)->toContain('>Romans 8:31</a>');
});

test('links references inside other inline elements like blockquote', function (): void {
    $result = linkify('<blockquote>John 1:14 grounds the whole night.</blockquote>');

    expect($result)->toContain('>John 1:14</a>')
        ->and($result)->toContain('<blockquote>');
});

test('preserves the surrounding HTML structure', function (): void {
    $result = linkify('<p>Hello <strong>world</strong> Romans 8:31.</p>');

    expect($result)->toContain('<strong>world</strong>')
        ->and($result)->toContain('>Romans 8:31</a>');
});

test('returns an empty string unchanged', function (): void {
    expect(linkify(''))->toBe('');
});

test('returns whitespace input unchanged', function (): void {
    expect(linkify('   '))->toBe('   ');
});

test('does not link a token that looks like a verse but is not after a book', function (): void {
    $result = linkify('<p>The number 8:31 alone is not a scripture.</p>');

    expect($result)->not->toContain('scripture-ref');
});

test('handles the Psalm singular form from the canonical list', function (): void {
    $result = linkify('<p>Psalm 23:1 echoes the same.</p>');

    expect($result)->toContain('>Psalm 23:1</a>');
});

test('handles a numeric-prefixed book whose last token is canonical', function (): void {
    $result = linkify('<p>1 John 1:14 is the line.</p>');

    expect($result)->toContain('>1 John 1:14</a>');
});

test('wraps an abbreviated single reference', function (): void {
    $result = linkify('<p>Rom 3:23 is sobering.</p>');

    expect($result)->toContain('>Rom 3:23</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/romans/3?verse=23"');
});

test('wraps an abbreviated numeric-prefixed reference', function (): void {
    $result = linkify('<p>1 Cor 1:1 opens the letter.</p>');

    expect($result)->toContain('>1 Cor 1:1</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/1-corinthians/1?verse=1"');
});

test('wraps an abbreviation with a trailing period', function (): void {
    $result = linkify('<p>Rom. 8:31 is the closer.</p>');

    expect($result)->toContain('>Rom. 8:31</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/romans/8?verse=31"');
});

test('wraps an abbreviated verse range with a hyphen', function (): void {
    $result = linkify('<p>Rom 8:31-39 is the arc.</p>');

    expect($result)->toContain('>Rom 8:31-39</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/romans/8?verse=31&amp;endVerse=39"');
});

test('wraps an abbreviated verse range with an en-dash', function (): void {
    $result = linkify('<p>Rom 8:31–39 is the arc.</p>');

    expect($result)->toContain('>Rom 8:31–39</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/romans/8?verse=31&amp;endVerse=39"');
});

test('disambiguates Phil as Philippians', function (): void {
    $result = linkify('<p>Phil 4:13 reframes strength.</p>');

    expect($result)->toContain('>Phil 4:13</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/philippians/4?verse=13"');
});

test('disambiguates Phlm as Philemon', function (): void {
    $result = linkify('<p>Phlm 1:6 ties faith to action.</p>');

    expect($result)->toContain('>Phlm 1:6</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/philemon/1?verse=6"');
});

test('maps the Ps abbreviation through to the psalms slug', function (): void {
    $result = linkify('<p>Ps 23:1 echoes the same.</p>');

    expect($result)->toContain('>Ps 23:1</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/psalms/23?verse=1"');
});

test('wraps gospel abbreviation Mt', function (): void {
    $result = linkify('<p>Mt 5:3 opens the sermon.</p>');

    expect($result)->toContain('>Mt 5:3</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/matthew/5?verse=3"');
});

test('wraps gospel abbreviation Mk', function (): void {
    $result = linkify('<p>Mk 1:1 starts the gospel.</p>');

    expect($result)->toContain('>Mk 1:1</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/mark/1?verse=1"');
});

test('wraps gospel abbreviation Lk', function (): void {
    $result = linkify('<p>Lk 2:11 announces the birth.</p>');

    expect($result)->toContain('>Lk 2:11</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/luke/2?verse=11"');
});

test('wraps gospel abbreviation Jn', function (): void {
    $result = linkify('<p>Jn 3:16 is the line.</p>');

    expect($result)->toContain('>Jn 3:16</a>')
        ->and($result)->toContain('href="https://www.merebible.app/read/csb/john/3?verse=16"');
});

test('does not link an unrecognized abbreviation', function (): void {
    $result = linkify('<p>Xyz 1:1 and Foo. 2:2 should not link.</p>');

    expect($result)->not->toContain('scripture-ref');
});

test('does not false-positive on a non-book period token', function (): void {
    $result = linkify('<p>Mr. 8:31 should not link.</p>');

    expect($result)->not->toContain('scripture-ref');
});
