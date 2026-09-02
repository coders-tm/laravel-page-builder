<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Schema;

use PageBuilder\Schema\BlockSchema;
use PageBuilder\Schema\SettingSchema;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BlockSchemaTest extends TestCase
{
    public function test_it_creates_from_valid_array(): void
    {
        $schema = new BlockSchema([
            'type' => 'row',
            'name' => 'Row',
            'settings' => [
                ['id' => 'columns', 'type' => 'select', 'default' => '2'],
            ],
            'blocks' => [
                ['type' => 'column'],
            ],
            'presets' => [
                ['name' => 'Two Columns'],
            ],
        ]);

        $this->assertSame('row', $schema->type);
        $this->assertSame('Row', $schema->name);
        $this->assertCount(1, $schema->settings);
        $this->assertInstanceOf(SettingSchema::class, $schema->settings[0]);
        $this->assertCount(1, $schema->blocks);
        $this->assertCount(1, $schema->presets);
    }

    public function test_it_throws_when_name_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("missing required 'name'");

        new BlockSchema(['type' => 'row']);
    }

    public function test_it_throws_when_type_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("missing required 'type'");

        new BlockSchema(['name' => 'Row']);
    }

    public function test_it_throws_when_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BlockSchema(['type' => 'row', 'name' => '']);
    }

    public function test_accepts_theme_blocks(): void
    {
        $schema = new BlockSchema([
            'type' => 'column',
            'name' => 'Column',
            'blocks' => [['type' => '@theme']],
        ]);

        $this->assertTrue($schema->acceptsThemeBlocks());
    }

    public function test_does_not_accept_theme_blocks_without_wildcard(): void
    {
        $schema = new BlockSchema([
            'type' => 'row',
            'name' => 'Row',
            'blocks' => [['type' => 'column']],
        ]);

        $this->assertFalse($schema->acceptsThemeBlocks());
    }

    public function test_setting_defaults(): void
    {
        $schema = new BlockSchema([
            'type' => 'row',
            'name' => 'Row',
            'settings' => [
                ['id' => 'columns', 'type' => 'select', 'default' => '2'],
                ['id' => 'gap', 'type' => 'select', 'default' => 'md'],
                ['id' => 'no_default', 'type' => 'text'],
            ],
        ]);

        $defaults = $schema->settingDefaults();

        $this->assertSame('2', $defaults['columns']);
        $this->assertSame('md', $defaults['gap']);
        $this->assertNull($defaults['no_default']);
    }

    public function test_to_array_roundtrip(): void
    {
        $data = [
            'type' => 'row',
            'name' => 'Row',
            'settings' => [
                ['id' => 'columns', 'type' => 'select', 'default' => '2'],
            ],
            'blocks' => [['type' => 'column']],
            'presets' => [['name' => 'Two Columns']],
        ];

        $schema = new BlockSchema($data);
        $array = $schema->toArray();

        $this->assertSame('row', $array['type']);
        $this->assertSame('Row', $array['name']);
        $this->assertCount(1, $array['settings']);
        $this->assertCount(1, $array['blocks']);
        $this->assertCount(1, $array['presets']);
    }

    public function test_json_serialization(): void
    {
        $schema = new BlockSchema([
            'type' => 'column',
            'name' => 'Column',
        ]);

        $json = json_encode($schema);
        $decoded = json_decode($json, true);

        $this->assertSame('column', $decoded['type']);
        $this->assertSame('Column', $decoded['name']);
    }

    public function test_empty_settings_and_blocks(): void
    {
        $schema = new BlockSchema([
            'type' => 'divider',
            'name' => 'Divider',
        ]);

        $this->assertSame([], $schema->settings);
        $this->assertSame([], $schema->blocks);
        $this->assertSame([], $schema->presets);
        $this->assertSame([], $schema->settingDefaults());
    }
}
