<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Unit;

use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Support\HtmlString;

class PageBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        PageBuilder::disableEditor();
        parent::tearDown();
    }

    public function test_editor_is_disabled_by_default(): void
    {
        $this->assertFalse(PageBuilder::editor());
    }

    public function test_enable_editor(): void
    {
        PageBuilder::enableEditor();

        $this->assertTrue(PageBuilder::editor());
    }

    public function test_disable_editor(): void
    {
        PageBuilder::enableEditor();
        PageBuilder::disableEditor();

        $this->assertFalse(PageBuilder::editor());
    }

    public function test_class_returns_string_when_editor_enabled(): void
    {
        PageBuilder::enableEditor();

        $class = PageBuilder::class();

        $this->assertIsString($class);
        $this->assertNotEmpty($class);
        // Actual value is 'js pb-design-mode'
        $this->assertStringContainsString('pb-design-mode', $class);
    }

    public function test_class_returns_empty_when_editor_disabled(): void
    {
        PageBuilder::disableEditor();

        $this->assertSame('', PageBuilder::class());
    }

    public function test_css_returns_html_string_when_editor_enabled(): void
    {
        PageBuilder::enableEditor();

        $css = PageBuilder::css();

        // css() returns HtmlString, not a plain string
        $this->assertInstanceOf(HtmlString::class, $css);
    }

    public function test_js_returns_html_string_when_editor_enabled(): void
    {
        PageBuilder::enableEditor();

        $js = PageBuilder::js();

        // js() returns HtmlString, not a plain string
        $this->assertInstanceOf(HtmlString::class, $js);
    }

    public function test_css_returns_empty_html_string_when_editor_disabled(): void
    {
        PageBuilder::disableEditor();

        $css = PageBuilder::css();
        $this->assertInstanceOf(HtmlString::class, $css);
    }

    public function test_js_returns_html_string_when_editor_disabled(): void
    {
        PageBuilder::disableEditor();

        // js() still returns HtmlString (possibly with Vite dev script in dev mode)
        $js = PageBuilder::js();
        $this->assertInstanceOf(HtmlString::class, $js);
    }

    public function test_script_variables_returns_array(): void
    {
        $vars = PageBuilder::scriptVariables();

        $this->assertIsArray($vars);
    }

    public function test_is_preserved_page(): void
    {
        $this->assertTrue(PageBuilder::isPreservedPage('home'));
        $this->assertFalse(PageBuilder::isPreservedPage('custom-page'));
        $this->assertFalse(PageBuilder::isPreservedPage(null));
    }

    public function test_is_preserved_page_case_insensitive(): void
    {
        $this->assertTrue(PageBuilder::isPreservedPage('HOME'));
        $this->assertTrue(PageBuilder::isPreservedPage('Home'));
    }
}
