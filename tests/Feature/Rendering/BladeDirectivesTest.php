<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Rendering;

use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Rendering\BladeDirectives;
use Coderstm\PageBuilder\Support\PageData;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class BladeDirectivesTest extends TestCase
{
    protected function tearDown(): void
    {
        PageBuilder::disableEditor();
        parent::tearDown();
    }

    public function test_schema_directive_is_noop(): void
    {
        $blade = '@schema(["name" => "Test"])';
        $compiled = $this->compileBladeString($blade);

        $this->assertSame('<?php /* @schema */ ?>', $compiled);
    }

    public function test_pb_editor_class_directive(): void
    {
        $compiled = $this->compileBladeString('@pbEditorClass');

        $this->assertStringContainsString('PageBuilder::classAttribute()', $compiled);
    }

    public function test_pb_editor_class_directive_accepts_classes(): void
    {
        $compiled = $this->compileBladeString("@pbEditorClass('foo', 'bar')");

        $this->assertStringContainsString("PageBuilder::classAttribute('foo', 'bar')", $compiled);
    }

    public function test_pb_editor_class_outputs_empty_when_disabled(): void
    {
        PageBuilder::disableEditor();

        $this->assertSame('', PageBuilder::class());
    }

    public function test_pb_editor_class_outputs_class_when_enabled(): void
    {
        PageBuilder::enableEditor();

        $class = PageBuilder::class();

        $this->assertNotEmpty($class);
        // PageBuilder::class() returns 'js pb-design-mode' when editor is enabled
        $this->assertStringContainsString('pb-design-mode', $class);
    }

    public function test_blocks_directive_compiles_for_section(): void
    {
        $compiled = $this->compileBladeString('@blocks($section)');

        $this->assertStringContainsString('renderBlocks', $compiled);
        $this->assertStringContainsString('renderBlockChildren', $compiled);
    }

    public function test_blocks_directive_compiles_for_block(): void
    {
        $compiled = $this->compileBladeString('@blocks($block)');

        $this->assertStringContainsString('renderBlockChildren', $compiled);
    }

    // ─── @sections Directive Tests ─────────────────────────────

    public function test_sections_directive_compiles(): void
    {
        $compiled = $this->compileBladeString("@sections('header')");

        $this->assertStringContainsString('renderLayoutSection', $compiled);
        $this->assertStringContainsString('__pb_layout', $compiled);
        $this->assertStringContainsString("'header'", $compiled);
    }

    public function test_sections_directive_compiles_with_key_only(): void
    {
        // @sections() takes a single key — no second argument.
        // Zone membership (header vs footer) is resolved at runtime via layoutSection().
        $compiled = $this->compileBladeString("@sections('footer')");

        $this->assertStringContainsString('renderLayoutSection', $compiled);
        $this->assertStringContainsString('__pb_layout', $compiled);
        $this->assertStringContainsString("'footer'", $compiled);
    }

    public function test_layout_section_settings_render_blade_syntax(): void
    {
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
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Compile a Blade string and return the compiled PHP output.
     * Named compileBladeString to avoid collision with Testbench's blade() method.
     */
    private function compileBladeString(string $value): string
    {
        return Blade::compileString($value);
    }
}
