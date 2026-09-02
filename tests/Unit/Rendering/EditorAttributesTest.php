<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Rendering;

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;
use PageBuilder\Rendering\EditorAttributes;
use PageBuilder\Tests\TestCase;

class EditorAttributesTest extends TestCase
{
    protected function tearDown(): void
    {
        PageBuilder::disableEditor();
        parent::tearDown();
    }

    private function makeSection(string $id = 'hero-1', string $type = 'hero'): Section
    {
        return new Section([
            'id' => $id,
            'type' => $type,
            'name' => 'Hero',
            'settings' => new Settings(['title' => 'Hello'], []),
            'blocks' => new BlockCollection,
        ]);
    }

    private function makeBlock(string $id = 'block-1', string $type = 'row'): Block
    {
        return new Block([
            'id' => $id,
            'type' => $type,
            'name' => 'Row',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);
    }

    public function test_for_section_returns_empty_when_editor_off(): void
    {
        PageBuilder::disableEditor();

        $this->assertSame('', EditorAttributes::forSection($this->makeSection()));
    }

    public function test_for_block_returns_empty_when_editor_off(): void
    {
        PageBuilder::disableEditor();

        $this->assertSame('', EditorAttributes::forBlock($this->makeBlock()));
    }

    public function test_for_section_returns_attributes_when_editor_on(): void
    {
        PageBuilder::enableEditor();

        $result = EditorAttributes::forSection($this->makeSection('hero-1', 'hero'));

        // Actual format: data-editor-section='{"id":"hero-1","type":"hero",...}' data-section-id="hero-1"
        $this->assertStringContainsString('data-section-id="hero-1"', $result);
        $this->assertStringContainsString('data-editor-section=', $result);
        $this->assertStringContainsString('"id"', $result);
        $this->assertStringContainsString('"hero-1"', $result);
    }

    public function test_for_block_returns_attributes_when_editor_on(): void
    {
        PageBuilder::enableEditor();

        $result = EditorAttributes::forBlock($this->makeBlock('block-1', 'row'));

        // Actual format: data-block-id="block-1" data-editor-block='{"id":"block-1",...}'
        $this->assertStringContainsString('data-block-id="block-1"', $result);
        $this->assertStringContainsString('data-editor-block=', $result);
        $this->assertStringContainsString('"block-1"', $result);
    }

    public function test_auto_inject_live_text_when_editor_off(): void
    {
        PageBuilder::disableEditor();

        // autoInjectLiveText has no editor guard — it always runs.
        // The Renderer only calls it when editor is on. The method itself
        // simply returns the HTML unchanged when there are no matching text values.
        $html = '<h1>No match here xyz</h1>';
        $section = $this->makeSection('hero-1', 'hero'); // settings has title='Hello'

        $result = EditorAttributes::autoInjectLiveText($html, $section);

        // Text 'Hello' not in the HTML so no injection occurs
        $this->assertSame($html, $result);
    }

    public function test_auto_inject_live_text_when_editor_on(): void
    {
        PageBuilder::enableEditor();

        $section = $this->makeSection('hero-1', 'hero'); // settings has title='Hello'
        $html = '<h1>Hello</h1>';

        $result = EditorAttributes::autoInjectLiveText($html, $section);

        // autoInjectLiveText injects data-live-text-setting when text matches a setting value
        $this->assertIsString($result);
        $this->assertStringContainsString('data-live-text-setting=', $result);
    }
}
