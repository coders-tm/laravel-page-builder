<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Collections;

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Collections\SectionCollection;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\Tests\TestCase;

class SectionCollectionTest extends TestCase
{
    private function makeSection(string $id, string $type = 'section', bool $disabled = false): Section
    {
        return new Section([
            'id' => $id,
            'type' => $type,
            'disabled' => $disabled,
            'settings' => new Settings([], []),
            'blocks' => new BlockCollection,
        ]);
    }

    public function test_empty_collection(): void
    {
        $collection = new SectionCollection;

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->count());
    }

    public function test_enabled_filters_disabled_sections(): void
    {
        $sections = [
            $this->makeSection('s1', 'hero', false),
            $this->makeSection('s2', 'footer', true),
            $this->makeSection('s3', 'content', false),
        ];

        $collection = new SectionCollection($sections);
        $enabled = $collection->enabled();

        $this->assertInstanceOf(SectionCollection::class, $enabled);
        $this->assertSame(2, $enabled->count());
        $this->assertSame('s1', $enabled->first()->id);
        $this->assertSame('s3', $enabled->last()->id);
    }

    public function test_of_type(): void
    {
        $sections = [
            $this->makeSection('s1', 'hero'),
            $this->makeSection('s2', 'hero'),
            $this->makeSection('s3', 'footer'),
        ];

        $collection = new SectionCollection($sections);
        $heroes = $collection->ofType('hero');

        $this->assertSame(2, $heroes->count());
    }

    public function test_find(): void
    {
        // SectionCollection::find() looks up by string key — items must be keyed by id
        $collection = new SectionCollection([
            's1' => $this->makeSection('s1', 'hero'),
            's2' => $this->makeSection('s2', 'footer'),
        ]);

        $this->assertSame('s1', $collection->find('s1')->id);
        $this->assertNull($collection->find('missing'));
    }

    public function test_to_array(): void
    {
        $sections = [
            $this->makeSection('s1', 'hero'),
        ];

        $collection = new SectionCollection($sections);
        $array = $collection->toArray();

        $this->assertCount(1, $array);
        $this->assertSame('s1', $array[0]['id']);
    }
}
