<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Rendering;

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;
use PageBuilder\Rendering\Renderer;
use PageBuilder\Tests\TestCase;

class RendererTest extends TestCase
{
    private Renderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = $this->app->make(Renderer::class);
    }

    protected function tearDown(): void
    {
        PageBuilder::disableEditor();
        parent::tearDown();
    }

    public function test_hydrate_section(): void
    {
        $section = $this->renderer->hydrateSection('hero-1', [
            'type' => 'hero',
            'settings' => ['title' => 'Custom Title'],
            'blocks' => [],
        ]);

        $this->assertInstanceOf(Section::class, $section);
        $this->assertSame('hero-1', $section->id);
        $this->assertSame('hero', $section->type);
        $this->assertSame('Hero', $section->name);
        $this->assertSame('Custom Title', $section->settings->title);
        // Default should apply for non-provided settings
        $this->assertSame('Hello World', $section->settings->subtitle);
    }

    public function test_hydrate_section_with_nested_blocks(): void
    {
        $section = $this->renderer->hydrateSection('content-1', [
            'type' => 'content',
            'settings' => [],
            'blocks' => [
                'row-1' => [
                    'type' => 'row',
                    'settings' => ['columns' => '3'],
                    'blocks' => [],
                ],
            ],
            'order' => ['row-1'],
        ]);

        $this->assertSame(1, $section->blocks->count());
        $this->assertSame('row-1', $section->blocks->first()->id);
        $this->assertSame('row', $section->blocks->first()->type);
        $this->assertSame('3', $section->blocks->first()->settings->columns);
    }

    public function test_hydrate_section_skips_disabled_blocks(): void
    {
        $section = $this->renderer->hydrateSection('test-1', [
            'type' => 'test',
            'settings' => [],
            'blocks' => [
                'b1' => ['type' => 'text', 'settings' => [], 'disabled' => false],
                'b2' => ['type' => 'text', 'settings' => [], 'disabled' => true],
            ],
            'order' => ['b1', 'b2'],
        ]);

        $this->assertSame(1, $section->blocks->count());
        $this->assertSame('b1', $section->blocks->first()->id);
    }

    public function test_render_section(): void
    {
        $section = $this->renderer->hydrateSection('s1', [
            'type' => 'simple',
            'settings' => ['heading' => 'Test Heading'],
        ]);

        $html = $this->renderer->renderSection($section);

        $this->assertStringContainsString('<h1>Test Heading</h1>', $html);
        $this->assertStringContainsString('<section >', $html);
    }

    public function test_render_section_returns_comment_for_unknown_type(): void
    {
        $section = new Section([
            'id' => 's1',
            'type' => 'unknown',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);

        $html = $this->renderer->renderSection($section);

        $this->assertStringContainsString('not found', $html);
    }

    public function test_render_block(): void
    {
        $block = new Block([
            'id' => 'b1',
            'type' => 'text',
            'settings' => new Settings(['content' => 'Hello World'], []),
            'blocks' => new BlockCollection,
        ]);

        $html = $this->renderer->renderBlock($block);

        $this->assertStringContainsString('Hello World', $html);
        $this->assertStringContainsString('text-block', $html);
    }

    public function test_render_block_returns_comment_for_unknown_view(): void
    {
        $block = new Block([
            'id' => 'b1',
            'type' => 'nonexistent-block-type',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);

        $html = $this->renderer->renderBlock($block);

        $this->assertStringContainsString('not found', $html);
    }

    public function test_render_raw_section(): void
    {
        $html = $this->renderer->renderRawSection('raw-1', [
            'type' => 'raw',
            'settings' => ['text' => 'Preview Text'],
        ]);

        $this->assertStringContainsString('Preview Text', $html);
    }
}
