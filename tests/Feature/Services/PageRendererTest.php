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
use PageBuilder\Services\PageRenderer;
use PageBuilder\Support\PageData;

beforeEach(function () {
    $this->pageRenderer = $this->app->make(PageRenderer::class);
});
afterEach(function () {
    PageBuilder::disableEditor();
});
test('render by slug', function () {
    $this->get('/')->assertSee('Build High-Performance Page Builders');
});
test('render page from array', function () {
    $html = $this->pageRenderer->renderPage([
        'sections' => [
            's1' => [
                'type' => 'banner',
                'settings' => ['text' => 'Array Page'],
                'blocks' => [],
            ],
        ],
        'order' => ['s1'],
    ]);

    $this->assertStringContainsString('Array Page', $html);
});
test('render page from page data', function () {
    $pageData = PageData::fromArray([
        'sections' => [
            's1' => [
                'type' => 'banner',
                'settings' => ['text' => 'PageData Page'],
                'blocks' => [],
            ],
        ],
        'order' => ['s1'],
    ]);

    $html = $this->pageRenderer->renderPage($pageData);

    $this->assertStringContainsString('PageData Page', $html);
});
test('render page renders blade syntax in section and block settings', function () {
    $pageData = PageData::fromArray([
        'title' => 'Blade Settings Page',
        'sections' => [
            'banner-1' => [
                'type' => 'banner',
                'settings' => [
                    'text' => 'Section: {{ $page->title }} @if(config(\'app.name\') === \'My App\')ready @endif',
                ],
                'blocks' => [],
                'order' => [],
            ],
            'content-1' => [
                'type' => 'content',
                'settings' => [],
                'blocks' => [
                    'text-1' => [
                        'type' => 'text',
                        'settings' => [
                            'content' => 'Block: {{ strtoupper($page->title) }}',
                        ],
                        'blocks' => [],
                        'order' => [],
                    ],
                ],
                'order' => ['text-1'],
            ],
        ],
        'order' => ['banner-1', 'content-1'],
    ]);

    $html = $this->pageRenderer->renderPage($pageData);

    $this->assertStringContainsString('Section: Blade Settings Page ready', $html);
    $this->assertStringContainsString('Block: BLADE SETTINGS PAGE', $html);
    $this->assertStringNotContainsString('{{ $page->title }}', $html);
});
test('render page multiple sections in order', function () {
    $html = $this->pageRenderer->renderPage([
        'sections' => [
            'banner-1' => [
                'type' => 'banner',
                'settings' => ['text' => 'First'],
                'blocks' => [],
            ],
            'footer-1' => [
                'type' => 'footer',
                'blocks' => [],
            ],
        ],
        'order' => ['banner-1', 'footer-1'],
    ]);

    $bannerPos = strpos($html, 'First');
    $footerPos = strpos($html, 'All rights reserved.');

    $this->assertNotFalse($bannerPos);
    $this->assertNotFalse($footerPos);
    expect($bannerPos)->toBeLessThan($footerPos);
});
test('render page skips disabled sections', function () {
    $html = $this->pageRenderer->renderPage([
        'sections' => [
            's1' => [
                'type' => 'banner',
                'settings' => ['text' => 'Visible'],
                'blocks' => [],
            ],
            's2' => [
                'type' => 'footer',
                'settings' => ['copyright' => 'Hidden'],
                'blocks' => [],
                'disabled' => true,
            ],
        ],
        'order' => ['s1', 's2'],
    ]);

    $this->assertStringContainsString('Visible', $html);
    $this->assertStringNotContainsString('Hidden', $html);
});
test('render page skips missing section ids', function () {
    $html = $this->pageRenderer->renderPage([
        'sections' => [
            's1' => [
                'type' => 'banner',
                'settings' => ['text' => 'Only One'],
                'blocks' => [],
            ],
        ],
        'order' => ['s1', 'nonexistent-section'],
    ]);

    $this->assertStringContainsString('Only One', $html);
});
test('render empty page in production mode returns empty string', function () {
    PageBuilder::disableEditor();

    $html = $this->pageRenderer->renderPage([
        'sections' => [],
        'order' => [],
    ], editor: false);

    expect($html)->toBeEmpty();
    $this->assertStringNotContainsString('data-pb-ghost', $html);
});

test('render empty page in editor mode renders ghost element placeholder', function () {
    $html = $this->pageRenderer->renderPage([
        'sections' => [],
        'order' => [],
    ], editor: true);

    $this->assertStringContainsString('data-pb-ghost="true"', $html);
    $this->assertStringContainsString('ghost-section', $html);
    $this->assertStringContainsString('Empty Page', $html);
});

test('render empty page when PageBuilder editor is enabled renders ghost element placeholder', function () {
    PageBuilder::enableEditor();

    $html = $this->pageRenderer->renderPage([
        'sections' => [],
        'order' => [],
    ]);

    $this->assertStringContainsString('data-pb-ghost="true"', $html);
    $this->assertStringContainsString('ghost-section', $html);
});

test('render page renders disabled sections in editor mode', function () {
    PageBuilder::enableEditor();

    $html = $this->pageRenderer->renderPage([
        'sections' => [
            's1' => [
                'type' => 'banner',
                'settings' => ['text' => 'Visible'],
                'blocks' => [],
            ],
            's2' => [
                'type' => 'banner',
                'settings' => ['text' => 'Disabled Section'],
                'blocks' => [],
                'disabled' => true,
            ],
        ],
        'order' => ['s1', 's2'],
    ], editor: true);

    $this->assertStringContainsString('Visible', $html);
    $this->assertStringContainsString('Disabled Section', $html);
});

test('render page includes pb-disabled-section attribute on disabled sections in editor mode', function () {
    PageBuilder::enableEditor();

    $html = $this->pageRenderer->renderPage([
        'sections' => [
            's1' => [
                'type' => 'banner',
                'settings' => ['text' => 'Disabled Content'],
                'blocks' => [],
                'disabled' => true,
            ],
        ],
        'order' => ['s1'],
    ], editor: true);

    $this->assertStringContainsString('pb-disabled-section', $html);
    $this->assertStringContainsString('Disabled Content', $html);
});

test('render page skips disabled sections in production mode', function () {
    PageBuilder::disableEditor();

    $html = $this->pageRenderer->renderPage([
        'sections' => [
            's1' => [
                'type' => 'banner',
                'settings' => ['text' => 'Visible'],
                'blocks' => [],
            ],
            's2' => [
                'type' => 'banner',
                'settings' => ['text' => 'Should Not Appear'],
                'blocks' => [],
                'disabled' => true,
            ],
        ],
        'order' => ['s1', 's2'],
    ], editor: false);

    $this->assertStringContainsString('Visible', $html);
    $this->assertStringNotContainsString('Should Not Appear', $html);
    $this->assertStringNotContainsString('pb-disabled-section', $html);
});
