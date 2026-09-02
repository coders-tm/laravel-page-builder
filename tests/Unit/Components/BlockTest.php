<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Components;

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;
use PageBuilder\Tests\TestCase;

class BlockTest extends TestCase
{
    public function test_construction_with_full_data(): void
    {
        $block = new Block([
            'id' => 'block-1',
            'type' => 'row',
            'name' => 'Row Block',
            'disabled' => false,
            'settings' => new Settings(['columns' => '2'], []),
            'blocks' => new BlockCollection,
        ]);

        $this->assertSame('block-1', $block->id);
        $this->assertSame('row', $block->type);
        $this->assertSame('Row Block', $block->name);
        $this->assertFalse($block->disabled);
        $this->assertSame('2', $block->settings->columns);
    }

    public function test_default_type_is_block(): void
    {
        $block = new Block([
            'id' => 'block-1',
        ]);

        $this->assertSame('block', $block->type);
    }

    public function test_disabled_block(): void
    {
        $block = new Block([
            'id' => 'block-1',
            'type' => 'row',
            'disabled' => true,
        ]);

        $this->assertTrue($block->disabled);
    }

    public function test_editor_attributes_empty_when_editor_disabled(): void
    {
        PageBuilder::disableEditor();

        $block = new Block([
            'id' => 'block-1',
            'type' => 'row',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);

        $this->assertSame('', $block->editorAttributes());
    }

    public function test_nested_blocks(): void
    {
        $childBlock = new Block([
            'id' => 'col-1',
            'type' => 'column',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);

        $parentBlock = new Block([
            'id' => 'row-1',
            'type' => 'row',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection([$childBlock]),
        ]);

        $this->assertCount(1, $parentBlock->blocks);
        $this->assertSame('col-1', $parentBlock->blocks->first()->id);
    }

    public function test_to_array(): void
    {
        $block = new Block([
            'id' => 'block-1',
            'type' => 'row',
            'name' => 'Row',
            'settings' => new Settings(['columns' => '3'], []),
            'blocks' => new BlockCollection,
        ]);

        $array = $block->toArray();

        $this->assertSame('block-1', $array['id']);
        $this->assertSame('row', $array['type']);
        $this->assertSame('Row', $array['name']);
        $this->assertSame('3', $array['settings']['columns']);
    }

    public function test_json_serialization(): void
    {
        $block = new Block([
            'id' => 'block-1',
            'type' => 'column',
            'name' => 'Column',
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);

        $json = json_encode($block);
        $decoded = json_decode($json, true);

        $this->assertSame('block-1', $decoded['id']);
        $this->assertSame('column', $decoded['type']);
    }

    protected function tearDown(): void
    {
        PageBuilder::disableEditor();
        parent::tearDown();
    }
}
