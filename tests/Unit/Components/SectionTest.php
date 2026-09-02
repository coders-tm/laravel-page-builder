<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Components;

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;
use PageBuilder\Tests\TestCase;

class SectionTest extends TestCase
{
    public function test_construction_with_full_data(): void
    {
        $section = new Section([
            'id' => 'hero-1',
            'type' => 'hero',
            'name' => 'Hero Section',
            'disabled' => false,
            'settings' => new Settings(['title' => 'Welcome'], []),
            'blocks' => new BlockCollection,
        ]);

        $this->assertSame('hero-1', $section->id);
        $this->assertSame('hero', $section->type);
        $this->assertSame('Hero Section', $section->name);
        $this->assertFalse($section->disabled);
        $this->assertSame('Welcome', $section->settings->title);
        $this->assertCount(0, $section->blocks);
    }

    public function test_construction_with_minimal_data(): void
    {
        $section = new Section([]);

        $this->assertSame('', $section->id);
        $this->assertSame('', $section->type);
        $this->assertSame('', $section->name);
        $this->assertFalse($section->disabled);
    }

    public function test_disabled_section(): void
    {
        $section = new Section([
            'id' => 'hero-1',
            'type' => 'hero',
            'disabled' => true,
        ]);

        $this->assertTrue($section->disabled);
    }

    public function test_editor_attributes_empty_when_editor_disabled(): void
    {
        PageBuilder::disableEditor();

        $section = new Section([
            'id' => 'hero-1',
            'type' => 'hero',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);

        $this->assertSame('', $section->editorAttributes());
    }

    public function test_to_array(): void
    {
        $section = new Section([
            'id' => 'hero-1',
            'type' => 'hero',
            'name' => 'Hero',
            'settings' => new Settings(['title' => 'Hello'], ['title' => 'Default']),
            'blocks' => new BlockCollection,
        ]);

        $array = $section->toArray();

        $this->assertSame('hero-1', $array['id']);
        $this->assertSame('hero', $array['type']);
        $this->assertSame('Hero', $array['name']);
        $this->assertFalse($array['disabled']);
        $this->assertSame('Hello', $array['settings']['title']);
    }

    public function test_json_serialization(): void
    {
        $section = new Section([
            'id' => 'hero-1',
            'type' => 'hero',
            'name' => 'Hero',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);

        $json = json_encode($section);
        $decoded = json_decode($json, true);

        $this->assertSame('hero-1', $decoded['id']);
        $this->assertSame('hero', $decoded['type']);
    }

    protected function tearDown(): void
    {
        PageBuilder::disableEditor();
        parent::tearDown();
    }
}
