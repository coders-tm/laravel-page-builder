<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Registry;

use PageBuilder\Registry\BlockRegistry;
use PageBuilder\Schema\BlockSchema;
use PageBuilder\Tests\TestCase;

class BlockRegistryTest extends TestCase
{
    private BlockRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->app->make(BlockRegistry::class);
    }

    public function test_auto_discovers_blocks_from_path(): void
    {
        $this->assertTrue($this->registry->has('row'));

        $entry = $this->registry->get('row');
        $this->assertIsArray($entry);
        $this->assertInstanceOf(BlockSchema::class, $entry['schema']);
        $this->assertSame('Row', $entry['schema']->name);
        $this->assertSame('row', $entry['schema']->type);
    }

    public function test_prepare_raw_schema_injects_type(): void
    {
        $entry = $this->registry->get('column');
        $this->assertSame('column', $entry['schema']->type);
    }

    public function test_types_returns_all_registered(): void
    {
        $types = $this->registry->types();
        $this->assertContains('row', $types);
        $this->assertContains('column', $types);
    }

    public function test_has_returns_false_for_unregistered(): void
    {
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    public function test_register_manual_block(): void
    {
        $schema = new BlockSchema([
            'type' => 'text',
            'name' => 'Text Block',
        ]);

        $this->registry->register('text', $schema);

        $this->assertTrue($this->registry->has('text'));
        $entry = $this->registry->get('text');
        $this->assertSame('Text Block', $entry['schema']->name);
    }

    public function test_skips_blade_files_without_schema(): void
    {
        $this->assertFalse($this->registry->has('non-schema'));
    }
}
