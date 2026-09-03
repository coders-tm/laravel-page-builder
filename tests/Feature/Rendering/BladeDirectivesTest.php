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
test('pb editor class directive', function () {
    $compiled = compileBladeString('@pbEditorClass');

    $this->assertStringContainsString('PageBuilder::classAttribute()', $compiled);
});
test('pb editor class directive accepts classes', function () {
    $compiled = compileBladeString("@pbEditorClass('foo', 'bar')");

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
// ─── Helpers ────────────────────────────────────────────────
/**
 * Compile a Blade string and return the compiled PHP output.
 * Named compileBladeString to avoid collision with Testbench's blade() method.
 */
function compileBladeString(string $value): string
{
    return Blade::compileString($value);
}
