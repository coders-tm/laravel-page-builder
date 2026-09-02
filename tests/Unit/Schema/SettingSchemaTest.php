<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Schema;

use PageBuilder\Schema\SettingSchema;
use PHPUnit\Framework\TestCase;

class SettingSchemaTest extends TestCase
{
    public function test_it_creates_from_array(): void
    {
        $schema = new SettingSchema([
            'id' => 'title',
            'type' => 'text',
            'label' => 'Title',
            'default' => 'Hello',
        ]);

        $this->assertSame('title', $schema->id);
        $this->assertSame('text', $schema->type);
        $this->assertSame('Title', $schema->label);
        $this->assertSame('Hello', $schema->default);
    }

    public function test_it_defaults_type_to_text(): void
    {
        $schema = new SettingSchema(['id' => 'name']);

        $this->assertSame('text', $schema->type);
    }

    public function test_it_handles_missing_optional_fields(): void
    {
        $schema = new SettingSchema([]);

        $this->assertNull($schema->id);
        $this->assertSame('text', $schema->type);
        $this->assertNull($schema->label);
        $this->assertNull($schema->default);
        $this->assertNull($schema->info);
        $this->assertNull($schema->placeholder);
        $this->assertNull($schema->content);
        $this->assertNull($schema->options);
        $this->assertNull($schema->min);
        $this->assertNull($schema->max);
        $this->assertNull($schema->step);
        $this->assertNull($schema->unit);
    }

    public function test_it_parses_range_fields(): void
    {
        $schema = new SettingSchema([
            'id' => 'opacity',
            'type' => 'range',
            'min' => '0',
            'max' => '100',
            'step' => '5',
            'unit' => '%',
        ]);

        $this->assertSame(0, $schema->min);
        $this->assertSame(100, $schema->max);
        $this->assertSame(5, $schema->step);
        $this->assertSame('%', $schema->unit);
    }

    public function test_it_parses_select_options(): void
    {
        $options = [
            ['label' => 'Small', 'value' => 'sm'],
            ['label' => 'Large', 'value' => 'lg'],
        ];

        $schema = new SettingSchema([
            'id' => 'size',
            'type' => 'select',
            'options' => $options,
        ]);

        $this->assertSame($options, $schema->options);
    }

    public function test_to_array_excludes_nulls(): void
    {
        $schema = new SettingSchema([
            'id' => 'title',
            'type' => 'text',
            'label' => 'Title',
            'default' => 'Hello',
        ]);

        $array = $schema->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('default', $array);
        $this->assertArrayNotHasKey('info', $array);
        $this->assertArrayNotHasKey('placeholder', $array);
        $this->assertArrayNotHasKey('min', $array);
    }

    public function test_json_serialization(): void
    {
        $schema = new SettingSchema([
            'id' => 'bg',
            'type' => 'color',
            'default' => '#fff',
        ]);

        $json = json_encode($schema);
        $decoded = json_decode($json, true);

        $this->assertSame('bg', $decoded['id']);
        $this->assertSame('color', $decoded['type']);
        $this->assertSame('#fff', $decoded['default']);
    }
}
