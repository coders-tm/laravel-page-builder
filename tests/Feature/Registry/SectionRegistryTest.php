<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Registry;

use PageBuilder\Registry\SectionRegistry;
use PageBuilder\Schema\SectionSchema;
use PageBuilder\Tests\TestCase;

class SectionRegistryTest extends TestCase
{
    private SectionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->app->make(SectionRegistry::class);
    }

    public function test_auto_discovers_sections_from_path(): void
    {
        // The sections path is already added via config in TestCase::defineEnvironment

        $this->assertTrue($this->registry->has('hero'));

        $entry = $this->registry->get('hero');
        $this->assertIsArray($entry);
        $this->assertArrayHasKey('schema', $entry);
        $this->assertInstanceOf(SectionSchema::class, $entry['schema']);
        $this->assertSame('Hero', $entry['schema']->name);
    }

    public function test_types_returns_all_registered_types(): void
    {
        $types = $this->registry->types();
        $this->assertContains('hero', $types);
        $this->assertContains('footer', $types);
    }

    public function test_has_returns_false_for_unregistered(): void
    {
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    public function test_get_returns_null_for_unregistered(): void
    {
        $this->assertNull($this->registry->get('nonexistent'));
    }

    public function test_register_manual_schema(): void
    {
        $schema = new SectionSchema([
            'name' => 'Custom Section',
            'settings' => [],
        ]);

        $this->registry->register('custom', $schema);

        $this->assertTrue($this->registry->has('custom'));
        $entry = $this->registry->get('custom');
        $this->assertSame('Custom Section', $entry['schema']->name);
        $this->assertSame('sections.custom', $entry['view']);
    }

    public function test_get_all_returns_all_entries(): void
    {
        $all = $this->registry->get();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('hero', $all);
        $this->assertInstanceOf(SectionSchema::class, $all['hero']['schema']);
    }

    public function test_has_returns_false_for_invalid_schema(): void
    {
        $this->assertFalse($this->registry->has('non-schema-file-name'));
    }

    public function test_section_with_blocks_and_presets(): void
    {
        $entry = $this->registry->get('content');
        $schema = $entry['schema'];

        $this->assertSame('Content', $schema->name);
        $this->assertCount(0, $schema->settings);
        // Only entries with both 'type' and 'name' become BlockSchema objects in $blocks.
        // The bare ['type' => '@theme'] entry goes into $allowedBlockTypes, not $blocks.
        $this->assertCount(1, $schema->blocks);
        $this->assertCount(1, $schema->allowedBlockTypes);
        $this->assertCount(1, $schema->presets);
        $this->assertFalse($schema->acceptsThemeBlocks());
    }
}
