<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Collections;

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Settings;
use PageBuilder\Tests\TestCase;

class BlockCollectionTest extends TestCase
{
    private function makeBlock(string $id, string $type = 'block'): Block
    {
        return new Block([
            'id' => $id,
            'type' => $type,
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);
    }

    public function test_empty_collection(): void
    {
        $collection = new BlockCollection;

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
        $this->assertSame(0, $collection->count());
    }

    public function test_collection_with_items(): void
    {
        $blocks = [
            $this->makeBlock('b1', 'row'),
            $this->makeBlock('b2', 'column'),
        ];

        $collection = new BlockCollection($blocks);

        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
        $this->assertSame(2, $collection->count());
    }

    public function test_first_and_last(): void
    {
        $blocks = [
            $this->makeBlock('b1', 'row'),
            $this->makeBlock('b2', 'column'),
            $this->makeBlock('b3', 'text'),
        ];

        $collection = new BlockCollection($blocks);

        $this->assertSame('b1', $collection->first()->id);
        $this->assertSame('b3', $collection->last()->id);
    }

    public function test_nth(): void
    {
        $blocks = [
            $this->makeBlock('b1'),
            $this->makeBlock('b2'),
            $this->makeBlock('b3'),
        ];

        $collection = new BlockCollection($blocks);

        $this->assertSame('b1', $collection->nth(0)->id);
        $this->assertSame('b2', $collection->nth(1)->id);
        $this->assertSame('b3', $collection->nth(2)->id);
        $this->assertNull($collection->nth(99));
    }

    public function test_find(): void
    {
        // BlockCollection::find() looks up by string key — items must be keyed by id
        $collection = new BlockCollection([
            'b1' => $this->makeBlock('b1', 'row'),
            'b2' => $this->makeBlock('b2', 'column'),
        ]);

        $this->assertSame('b1', $collection->find('b1')->id);
        $this->assertNull($collection->find('nonexistent'));
    }

    public function test_of_type(): void
    {
        $blocks = [
            $this->makeBlock('b1', 'row'),
            $this->makeBlock('b2', 'column'),
            $this->makeBlock('b3', 'row'),
        ];

        $collection = new BlockCollection($blocks);
        $rows = $collection->ofType('row');

        $this->assertInstanceOf(BlockCollection::class, $rows);
        $this->assertSame(2, $rows->count());
    }

    public function test_map(): void
    {
        $blocks = [
            $this->makeBlock('b1', 'row'),
            $this->makeBlock('b2', 'column'),
        ];

        $collection = new BlockCollection($blocks);
        $ids = $collection->map(fn (Block $block) => $block->id);

        $this->assertSame(['b1', 'b2'], $ids);
    }

    public function test_iterable(): void
    {
        $blocks = [
            $this->makeBlock('b1'),
            $this->makeBlock('b2'),
        ];

        $collection = new BlockCollection($blocks);
        $ids = [];

        foreach ($collection as $block) {
            $ids[] = $block->id;
        }

        $this->assertSame(['b1', 'b2'], $ids);
    }

    public function test_array_access(): void
    {
        $blocks = [
            $this->makeBlock('b1'),
            $this->makeBlock('b2'),
        ];

        $collection = new BlockCollection($blocks);

        $this->assertTrue(isset($collection[0]));
        $this->assertSame('b1', $collection[0]->id);
        $this->assertFalse(isset($collection[99]));
    }

    public function test_to_array(): void
    {
        $blocks = [
            $this->makeBlock('b1', 'row'),
        ];

        $collection = new BlockCollection($blocks);
        $array = $collection->toArray();

        $this->assertCount(1, $array);
        $this->assertSame('b1', $array[0]['id']);
        $this->assertSame('row', $array[0]['type']);
    }

    public function test_json_serialization(): void
    {
        // BlockCollection is Arrayable but not JsonSerializable.
        // Use toArray() before encoding; items are keyed by id.
        $collection = new BlockCollection([
            'b1' => $this->makeBlock('b1', 'row'),
        ]);

        $json = json_encode($collection->toArray());
        $decoded = json_decode($json, true);

        $this->assertCount(1, $decoded);
        // toArray() keys items by id, so the JSON map has 'b1' as the key
        $this->assertArrayHasKey('b1', $decoded);
        $this->assertSame('b1', $decoded['b1']['id']);
    }
}
