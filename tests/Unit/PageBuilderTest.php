<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\HtmlString;
use PageBuilder\PageBuilder;

afterEach(function () {
    PageBuilder::disableEditor();
});
test('editor is disabled by default', function () {
    expect(PageBuilder::editor())->toBeFalse();
});
test('enable editor', function () {
    PageBuilder::enableEditor();

    expect(PageBuilder::editor())->toBeTrue();
});
test('disable editor', function () {
    PageBuilder::enableEditor();
    PageBuilder::disableEditor();

    expect(PageBuilder::editor())->toBeFalse();
});
test('class returns string when editor enabled', function () {
    PageBuilder::enableEditor();

    $class = PageBuilder::class();

    expect($class)->toBeString();
    expect($class)->not->toBeEmpty();

    // Actual value is 'js pb-design-mode'
    $this->assertStringContainsString('pb-design-mode', $class);
});
test('class returns empty when editor disabled', function () {
    PageBuilder::disableEditor();

    expect(PageBuilder::class())->toBe('');
});
test('class attribute returns empty without classes when editor disabled', function () {
    PageBuilder::disableEditor();

    expect(PageBuilder::classAttribute())->toBe('');
});
test('class attribute returns custom classes when editor disabled', function () {
    PageBuilder::disableEditor();

    expect(PageBuilder::classAttribute('dark'))->toBe('class="dark"');
});
test('class attribute merges custom classes with editor classes', function () {
    PageBuilder::enableEditor();

    expect(PageBuilder::classAttribute('foo', 'bar'))->toBe('class="foo bar js pb-design-mode"');
});
test('css returns html string when editor enabled', function () {
    PageBuilder::enableEditor();

    $css = PageBuilder::css();

    // css() returns HtmlString, not a plain string
    expect($css)->toBeInstanceOf(HtmlString::class);
});
test('js returns html string when editor enabled', function () {
    PageBuilder::enableEditor();

    $js = PageBuilder::js();

    // js() returns HtmlString, not a plain string
    expect($js)->toBeInstanceOf(HtmlString::class);
});
test('css returns empty html string when editor disabled', function () {
    PageBuilder::disableEditor();

    $css = PageBuilder::css();
    expect($css)->toBeInstanceOf(HtmlString::class);
});
test('js returns html string when editor disabled', function () {
    PageBuilder::disableEditor();

    // js() still returns HtmlString (possibly with Vite dev script in dev mode)
    $js = PageBuilder::js();
    expect($js)->toBeInstanceOf(HtmlString::class);
});
test('script variables returns array', function () {
    $vars = PageBuilder::scriptVariables();

    expect($vars)->toBeArray();
});
test('is preserved page', function () {
    expect(PageBuilder::isPreservedPage('home'))->toBeTrue();
    expect(PageBuilder::isPreservedPage('custom-page'))->toBeFalse();
    expect(PageBuilder::isPreservedPage(null))->toBeFalse();
});
test('is preserved page case insensitive', function () {
    expect(PageBuilder::isPreservedPage('HOME'))->toBeTrue();
    expect(PageBuilder::isPreservedPage('Home'))->toBeTrue();
});
