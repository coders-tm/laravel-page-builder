<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use PageBuilder\PageBuilder;
use PageBuilder\Rendering\BladeDirectives;
use PageBuilder\Support\PageData;

afterEach(function () {
    PageBuilder::disableEditor();
});

test('schema directive is noop', function () {
    $blade = '@schema(["name" => "Test"])';
    $compiled = compileBladeString($blade);

    expect($compiled)->toBe('<?php /* @schema */ ?>');
});

test('editor class directive', function () {
    $compiled = compileBladeString('@editor');

    $this->assertStringContainsString('PageBuilder::classAttribute()', $compiled);
});

test('editor class directive accepts classes', function () {
    $compiled = compileBladeString("@editor('foo', 'bar')");

    $this->assertStringContainsString("PageBuilder::classAttribute('foo', 'bar')", $compiled);
});

test('blocks directive compiles for section', function () {
    $compiled = compileBladeString('@blocks($section)');

    $this->assertStringContainsString('renderBlocks', $compiled);
    $this->assertStringContainsString('renderBlockChildren', $compiled);
});

test('blocks directive compiles for block', function () {
    $compiled = compileBladeString('@blocks($block)');

    $this->assertStringContainsString('renderBlockChildren', $compiled);
});

test('sections directive compiles', function () {
    $compiled = compileBladeString("@sections('header')");

    $this->assertStringContainsString('renderLayoutSection', $compiled);
    $this->assertStringContainsString('__pb_layout', $compiled);
    $this->assertStringContainsString("'header'", $compiled);
});

test('sections directive compiles with key only', function () {
    // @sections() takes a single key — no second argument.
    // Zone membership (header vs footer) is resolved at runtime via layoutSection().
    $compiled = compileBladeString("@sections('footer')");

    $this->assertStringContainsString('renderLayoutSection', $compiled);
    $this->assertStringContainsString('__pb_layout', $compiled);
    $this->assertStringContainsString("'footer'", $compiled);
});

test('layout section settings render blade syntax', function () {
    $layout = PageData::fromArray([
        'title' => 'Layout Blade Page',
        'layout' => [
            'type' => 'page',
            'header' => [
                'sections' => [
                    'header' => [
                        'type' => 'header',
                        'settings' => [
                            'title' => '{{ $page->title }} @if(config(\'app.name\') === \'My App\')header @endif',
                        ],
                        'blocks' => [],
                        'order' => [],
                    ],
                ],
                'order' => ['header'],
            ],
        ],
    ]);

    $html = BladeDirectives::renderLayoutSection($layout, 'header');

    $this->assertStringContainsString('Layout Blade Page header', $html);
    $this->assertStringNotContainsString('{{ $page->title }}', $html);
});

test('layout directive compiles to storePendingOverrides call', function () {
    $blade = "@layout(['header' => ['sections' => ['header' => ['settings' => ['sticky' => false]]]]])";
    $compiled = compileBladeString($blade);

    $this->assertStringContainsString('storePendingOverrides', $compiled);
    $this->assertStringContainsString('BladeDirectives', $compiled);
    // Expression is passed directly (not compiled through Blade::compileString)
    $this->assertStringContainsString("'sticky' => false", $compiled);
});

test('layout directive compiles with footer override', function () {
    $blade = "@layout(['footer' => ['sections' => ['footer' => ['settings' => ['tagline' => 'Custom']]]]])";
    $compiled = compileBladeString($blade);

    $this->assertStringContainsString('storePendingOverrides', $compiled);
    $this->assertStringContainsString('footer', $compiled);
});

test('layout directive compiles with both header and footer', function () {
    $blade = "@layout(['header' => ['sections' => []], 'footer' => ['sections' => []]])";
    $compiled = compileBladeString($blade);

    $this->assertStringContainsString('storePendingOverrides', $compiled);
});

test('storePendingOverrides and getPendingOverrides round trip', function () {
    $overrides = [
        'header' => [
            'sections' => [
                'header' => [
                    'settings' => ['sticky' => false],
                ],
            ],
        ],
    ];

    BladeDirectives::storePendingOverrides($overrides);
    $retrieved = BladeDirectives::getPendingOverrides();

    expect($retrieved)->toBe($overrides);

    // Cleared after retrieval
    $second = BladeDirectives::getPendingOverrides();
    expect($second)->toBe([]);
});

test('getPendingOverrides returns empty array when nothing stored', function () {
    $result = BladeDirectives::getPendingOverrides();
    expect($result)->toBe([]);
});

test('storePendingOverrides overwrites previous overrides', function () {
    BladeDirectives::storePendingOverrides(['header' => ['sections' => ['a' => []]]]);
    BladeDirectives::storePendingOverrides(['footer' => ['sections' => ['b' => []]]]);

    $result = BladeDirectives::getPendingOverrides();
    expect($result)->toHaveKey('footer');
    expect($result)->not->toHaveKey('header');
});

/**
 * Compile a Blade string and return the compiled PHP output.
 * Named compileBladeString to avoid collision with Testbench's blade() method.
 */
function compileBladeString(string $value): string
{
    return Blade::compileString($value);
}
