<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Unit\Support;

use Coderstm\PageBuilder\Support\WrapperParser;
use Coderstm\PageBuilder\Tests\TestCase;

class WrapperParserTest extends TestCase
{
    private WrapperParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new WrapperParser;
    }

    // ── parse() ──────────────────────────────────────────────────────────────

    public function test_parses_tag_only(): void
    {
        $result = $this->parser->parse('div');

        $this->assertSame('div', $result['tag']);
        $this->assertSame([], $result['attributes']);
    }

    public function test_parses_main_tag(): void
    {
        $result = $this->parser->parse('main');

        $this->assertSame('main', $result['tag']);
    }

    public function test_parses_section_tag(): void
    {
        $result = $this->parser->parse('section');

        $this->assertSame('section', $result['tag']);
    }

    public function test_falls_back_to_div_for_disallowed_tag(): void
    {
        $result = $this->parser->parse('article#some-id');

        $this->assertSame('div', $result['tag']);
        $this->assertSame('some-id', $result['attributes']['id']);
    }

    public function test_parses_id(): void
    {
        $result = $this->parser->parse('div#main-content');

        $this->assertSame('div', $result['tag']);
        $this->assertSame('main-content', $result['attributes']['id']);
    }

    public function test_parses_single_class(): void
    {
        $result = $this->parser->parse('div.container');

        $this->assertSame('container', $result['attributes']['class']);
    }

    public function test_parses_multiple_classes(): void
    {
        $result = $this->parser->parse('div.container.fluid.mx-auto');

        $this->assertSame('container fluid mx-auto', $result['attributes']['class']);
    }

    public function test_parses_id_and_class(): void
    {
        $result = $this->parser->parse('div#div_id.div_class');

        $this->assertSame('div_id', $result['attributes']['id']);
        $this->assertSame('div_class', $result['attributes']['class']);
    }

    public function test_parses_custom_attribute(): void
    {
        $result = $this->parser->parse('div[data-page=1]');

        $this->assertSame('1', $result['attributes']['data-page']);
    }

    public function test_parses_multiple_attributes(): void
    {
        $result = $this->parser->parse('div[attr-one=value1][attr-two=value2]');

        $this->assertSame('value1', $result['attributes']['attr-one']);
        $this->assertSame('value2', $result['attributes']['attr-two']);
    }

    public function test_parses_full_selector_from_spec_example(): void
    {
        // Example from the spec: div#div_id.div_class[attribute-one=value]
        $result = $this->parser->parse('div#div_id.div_class[attribute-one=value]');

        $this->assertSame('div', $result['tag']);
        $this->assertSame('div_id', $result['attributes']['id']);
        $this->assertSame('div_class', $result['attributes']['class']);
        $this->assertSame('value', $result['attributes']['attribute-one']);
    }

    public function test_parses_main_with_id(): void
    {
        $result = $this->parser->parse('main#page-content');

        $this->assertSame('main', $result['tag']);
        $this->assertSame('page-content', $result['attributes']['id']);
    }

    // ── render() ─────────────────────────────────────────────────────────────

    public function test_render_wraps_content_in_div(): void
    {
        $html = $this->parser->render('div', '<p>Hello</p>');

        $this->assertSame('<div><p>Hello</p></div>', $html);
    }

    public function test_render_with_id_and_class(): void
    {
        $html = $this->parser->render('div#div_id.div_class', 'content');

        $this->assertSame('<div id="div_id" class="div_class">content</div>', $html);
    }

    public function test_render_with_custom_attribute(): void
    {
        $html = $this->parser->render('div[attribute-one=value]', 'content');

        $this->assertSame('<div attribute-one="value">content</div>', $html);
    }

    public function test_render_full_spec_example(): void
    {
        $html = $this->parser->render('div#div_id.div_class[attribute-one=value]', '<!-- sections -->');

        $this->assertSame(
            '<div id="div_id" class="div_class" attribute-one="value"><!-- sections --></div>',
            $html,
        );
    }

    public function test_render_main_wrapper(): void
    {
        $html = $this->parser->render('main#page-content.wrapper', '<section>x</section>');

        $this->assertSame('<main id="page-content" class="wrapper"><section>x</section></main>', $html);
    }

    public function test_render_escapes_attribute_values(): void
    {
        $html = $this->parser->render('div#id<script>', 'content');

        $this->assertStringNotContainsString('<script>', $html);
    }
}
