<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\PageBuilder;

afterEach(function () {
    PageBuilder::setLang(null);
});

test('lang is null by default', function () {
    expect(PageBuilder::getLang())->toBeNull();
});

test('set lang', function () {
    PageBuilder::setLang('fr');

    expect(PageBuilder::getLang())->toBe('fr');
});

test('set lang to null resets to default', function () {
    PageBuilder::setLang('fr');
    PageBuilder::setLang(null);

    expect(PageBuilder::getLang())->toBeNull();
});

test('set lang to empty string resets to default', function () {
    PageBuilder::setLang('fr');
    PageBuilder::setLang('');

    expect(PageBuilder::getLang())->toBeNull();
});

test('set lang replaces previous value', function () {
    PageBuilder::setLang('fr');
    PageBuilder::setLang('de');

    expect(PageBuilder::getLang())->toBe('de');
});

test('lang is independent of editor mode', function () {
    PageBuilder::setLang('fr');
    PageBuilder::enableEditor();

    expect(PageBuilder::getLang())->toBe('fr');
    expect(PageBuilder::editor())->toBeTrue();

    PageBuilder::disableEditor();

    expect(PageBuilder::getLang())->toBe('fr');
    expect(PageBuilder::editor())->toBeFalse();
});
