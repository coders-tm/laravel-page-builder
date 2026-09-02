<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Registry;

use PageBuilder\Registry\SchemaExtractor;
use PageBuilder\Tests\TestCase;

class SchemaExtractorTest extends TestCase
{
    private SchemaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new SchemaExtractor;
    }

    private function getFixturePath(string $type, string $filename): string
    {
        return realpath(__DIR__.'/../../../workbench/resources/views/'.$type.'/'.$filename);
    }

    public function test_extract_valid_schema(): void
    {
        $path = $this->getFixturePath('sections', 'hero.blade.php');
        $result = $this->extractor->extract($path);

        $this->assertIsArray($result);
        $this->assertSame('Hero', $result['name']);
        $this->assertCount(2, $result['settings']);
        $this->assertSame('title', $result['settings'][0]['id']);
        $this->assertSame('subtitle', $result['settings'][1]['id']);
        $this->assertCount(0, $result['blocks'] ?? []);
        $this->assertCount(1, $result['presets']);
    }

    public function test_extract_returns_null_when_no_schema(): void
    {
        // plain.blade.php is now using a schema, so we test a layout which lacks one
        $path = $this->getFixturePath('layouts', 'page.blade.php');
        $this->assertNull($this->extractor->extract($path));
    }

    public function test_extract_handles_nested_brackets_and_multiline(): void
    {
        // row block schema is complex enough to test these conditions
        $path = $this->getFixturePath('blocks', 'row.blade.php');
        $result = $this->extractor->extract($path);

        $this->assertIsArray($result);
        $this->assertSame('Row', $result['name']);

        $columnsSetting = collect($result['settings'])->firstWhere('id', 'columns');
        $this->assertNotNull($columnsSetting);
        $this->assertIsArray($columnsSetting['options']);
        $this->assertCount(4, $columnsSetting['options']); // 1, 2, 3, 4 columns
    }

    public function test_extract_preserves_numeric_and_boolean_defaults(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'schema_test_').'.blade.php';
        file_put_contents($path, <<<'BLADE'
@schema([
    'name' => 'Types',
    'settings' => [
        ['id' => 'is_active', 'type' => 'checkbox', 'default' => true],
        ['id' => 'count', 'type' => 'number', 'default' => 42],
    ]
])
BLADE);

        $result = $this->extractor->extract($path);
        unlink($path);

        $this->assertIsArray($result);

        $isActive = collect($result['settings'])->firstWhere('id', 'is_active');
        $this->assertTrue($isActive['default']);

        $count = collect($result['settings'])->firstWhere('id', 'count');
        $this->assertSame(42, $count['default']);
    }
}
