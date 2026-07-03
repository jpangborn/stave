<?php

use Livewire\Features\SupportTesting\Testable;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Livewire island testing macros
|--------------------------------------------------------------------------
|
| Livewire islands only render their real content in response to the
| `__lazyLoadIsland` wire call (or a regular update after mounting); on
| initial render they show only their placeholder. These macros let tests
| trigger an island's load and inspect the resulting fragment HTML.
|
| Fragment shape (confirmed empirically): $component->effects['islandFragments']
| is a plain array of raw HTML strings, each wrapped in HTML-comment fragment
| markers: "<!--[if FRAGMENT:type=island|name={name}|token=...|mode=morph]><![endif]-->"
| followed by the rendered island HTML, then a matching ENDFRAGMENT marker.
| A never-mounted (role-gated) island has no entry in the component's islands
| memo, so SupportIslands::call no-ops and no fragment is produced at all.
|
*/

Testable::macro('loadIsland', function (string $name) {
    /** @var Testable $this */
    return $this->update(calls: [[
        'method' => '__lazyLoadIsland',
        'params' => [],
        'metadata' => ['island' => ['name' => $name, 'mode' => 'morph']],
    ]]);
});

Testable::macro('assertIslandSee', function (string $name, string $text) {
    /** @var Testable $this */
    $fragments = collect($this->effects['islandFragments'] ?? [])
        ->filter(fn ($fragment) => str_contains($fragment, "name={$name}|"));

    Assert::assertNotEmpty(
        $fragments,
        "No island fragment was rendered for island [{$name}]. Either the island was never mounted (role-gated and not loaded for this user) or loadIsland() was not called first."
    );

    $fragments->each(fn ($fragment) => Assert::assertStringContainsString($text, $fragment));

    return $this;
});
