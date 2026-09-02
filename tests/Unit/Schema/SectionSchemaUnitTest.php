<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Schema;

use PageBuilder\Schema\BlockSchema;
use PageBuilder\Schema\SectionSchema;
use PageBuilder\Schema\SettingSchema;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SectionSchemaUnitTest extends TestCase
{
    public function test_it_creates_from_valid_array(): void
    {
        $schema = new SectionSchema([
            'name' => 'Hero',
            'tag' => 'section',
            'class' => 'hero-section',
            'settings' => [
                ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome'],
            ],
            'blocks' => [
                ['type' => 'row', 'name' => 'Row', 'settings' => []],
            ],
            'presets' => [
                ['name' => 'Hero'],
            ],
        ]);

        $this->assertSame('Hero', $schema->name);
        $this->assertSame('section', $schema->tag);
        $this->assertSame('hero-section', $schema->class);
        $this->assertCount(1, $schema->settings);
        $this->assertInstanceOf(SettingSchema::class, $schema->settings[0]);
        $this->assertCount(1, $schema->blocks);
        $this->assertCount(1, $schema->presets);
    }

    public function test_it_throws_when_name_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("missing required 'name'");

        new SectionSchema([]);
    }

    public function test_default_values(): void
    {
        $schema = new SectionSchema(['name' => 'Simple']);

        $this->assertSame('Simple', $schema->name);
        $this->assertSame('section', $schema->tag);
        $this->assertSame('', $schema->class);
        $this->assertSame([], $schema->settings);
        $this->assertSame([], $schema->blocks);
        $this->assertSame([], $schema->presets);
        // limit and maxBlocks default to 0 (int), not null
        $this->assertSame(0, $schema->limit);
        $this->assertSame(0, $schema->maxBlocks);
    }

    public function test_limit_and_max_blocks(): void
    {
        $schema = new SectionSchema([
            'name' => 'Limited',
            'limit' => 3,
            'max_blocks' => 10,
        ]);

        $this->assertSame(3, $schema->limit);
        $this->assertSame(10, $schema->maxBlocks);
    }

    public function test_setting_defaults(): void
    {
        $schema = new SectionSchema([
            'name' => 'Hero',
            'settings' => [
                ['id' => 'title', 'type' => 'text', 'default' => 'Hello'],
                ['id' => 'subtitle', 'type' => 'text'],
                ['id' => 'show_button', 'type' => 'checkbox', 'default' => true],
            ],
        ]);

        $defaults = $schema->settingDefaults();

        $this->assertSame('Hello', $defaults['title']);
        $this->assertNull($defaults['subtitle']);
        $this->assertTrue($defaults['show_button']);
    }

    public function test_accepts_theme_blocks(): void
    {
        $schema = new SectionSchema([
            'name' => 'Flexible',
            'blocks' => [['type' => '@theme']],
        ]);

        $this->assertTrue($schema->acceptsThemeBlocks());
    }

    public function test_does_not_accept_theme_blocks(): void
    {
        $schema = new SectionSchema([
            'name' => 'Fixed',
            'blocks' => [
                ['type' => 'row', 'name' => 'Row'],
            ],
        ]);

        $this->assertFalse($schema->acceptsThemeBlocks());
    }

    public function test_block_schema_returns_local_definition(): void
    {
        $schema = new SectionSchema([
            'name' => 'Hero',
            'blocks' => [
                ['type' => 'column', 'name' => 'Column', 'settings' => [
                    ['id' => 'width', 'type' => 'select', 'default' => 'auto'],
                ]],
            ],
        ]);

        $blockSchema = $schema->blockSchema('column');

        $this->assertInstanceOf(BlockSchema::class, $blockSchema);
        $this->assertSame('column', $blockSchema->type);
        $this->assertSame('Column', $blockSchema->name);
        $this->assertCount(1, $blockSchema->settings);
    }

    public function test_block_schema_returns_null_for_unknown_type(): void
    {
        $schema = new SectionSchema([
            'name' => 'Hero',
            'blocks' => [
                ['type' => 'row', 'name' => 'Row'],
            ],
        ]);

        $this->assertNull($schema->blockSchema('unknown'));
    }

    public function test_allowed_block_types(): void
    {
        $schema = new SectionSchema([
            'name' => 'Multi',
            'blocks' => [
                ['type' => 'row', 'name' => 'Row'],
                ['type' => 'column', 'name' => 'Column'],
            ],
        ]);

        // allowedBlockTypes contains raw ['type' => '...'] arrays, not plain strings
        $this->assertSame(
            [['type' => 'row'], ['type' => 'column']],
            $schema->allowedBlockTypes
        );
    }

    public function test_to_array_includes_all_fields(): void
    {
        $schema = new SectionSchema([
            'name' => 'Hero',
            'tag' => 'div',
            'class' => 'hero',
            'limit' => 1,
            'max_blocks' => 5,
            'settings' => [
                ['id' => 'title', 'type' => 'text', 'default' => 'Hello'],
            ],
            'blocks' => [['type' => '@theme']],
            'presets' => [['name' => 'Default']],
        ]);

        $array = $schema->toArray();

        $this->assertSame('Hero', $array['name']);
        $this->assertSame('div', $array['tag']);
        $this->assertSame('hero', $array['class']);
        $this->assertSame(1, $array['limit']);
        $this->assertSame(5, $array['max_blocks']);
        $this->assertCount(1, $array['settings']);
        $this->assertCount(1, $array['blocks']);
        $this->assertCount(1, $array['presets']);
    }

    public function test_json_serialization(): void
    {
        $schema = new SectionSchema([
            'name' => 'Hero',
            'settings' => [
                ['id' => 'title', 'type' => 'text', 'default' => 'Welcome'],
            ],
        ]);

        $json = json_encode($schema);
        $decoded = json_decode($json, true);

        $this->assertSame('Hero', $decoded['name']);
        $this->assertCount(1, $decoded['settings']);
    }

    public function test_inline_block_definitions_create_block_schemas(): void
    {
        $schema = new SectionSchema([
            'name' => 'Section',
            'blocks' => [
                [
                    'type' => 'custom-block',
                    'name' => 'Custom Block',
                    'settings' => [
                        ['id' => 'color', 'type' => 'color', 'default' => '#000'],
                    ],
                    'blocks' => [['type' => '@theme']],
                ],
            ],
        ]);

        $block = $schema->blockSchema('custom-block');

        $this->assertInstanceOf(BlockSchema::class, $block);
        $this->assertSame('Custom Block', $block->name);
        $this->assertCount(1, $block->settings);
        $this->assertTrue($block->acceptsThemeBlocks());
    }

    public function test_bare_block_references_are_stored_as_raw_arrays(): void
    {
        $schema = new SectionSchema([
            'name' => 'Section',
            'blocks' => [
                ['type' => 'row'],
            ],
        ]);

        // Bare references (only 'type' key) should not be found as local BlockSchema
        // They need to be resolved from the theme registry at runtime.
        // allowedBlockTypes stores raw ['type' => '...'] arrays.
        $this->assertSame([['type' => 'row']], $schema->allowedBlockTypes);
    }
}
