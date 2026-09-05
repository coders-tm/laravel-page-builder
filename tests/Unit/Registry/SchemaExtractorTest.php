<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\Registry\SchemaExtractor;

beforeEach(function () {
    $this->extractor = new SchemaExtractor;
});
function getFixturePath(string $type, string $filename): string
{
    return resource_path('/views/'.$type.'/'.$filename);
}
test('extract valid schema', function () {
    $path = getFixturePath('sections', 'hero.blade.php');
    $result = $this->extractor->extract($path);

    expect($result)->toBeArray();
    expect($result['name'])->toBe('Hero');
    expect($result['settings'])->toHaveCount(4);
    expect($result['blocks'] ?? [])->toHaveCount(2);
    expect($result['presets'])->toHaveCount(1);
});
test('extract returns null when no schema', function () {
    // plain.blade.php is now using a schema, so we test a layout which lacks one
    $path = getFixturePath('layouts', 'page.blade.php');
    expect($this->extractor->extract($path))->toBeNull();
});
test('extract handles nested brackets and multiline', function () {
    // row block schema is complex enough to test these conditions
    $path = getFixturePath('blocks', 'row.blade.php');
    $result = $this->extractor->extract($path);

    expect($result)->toBeArray();
    expect($result['name'])->toBe('Row');

    $columnsSetting = collect($result['settings'])->firstWhere('id', 'columns');
    expect($columnsSetting)->not->toBeNull();
    expect($columnsSetting['options'])->toBeArray();
    expect($columnsSetting['options'])->toHaveCount(4);
    // 1, 2, 3, 4 columns
});
test('extract preserves numeric and boolean defaults', function () {
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

    expect($result)->toBeArray();

    $isActive = collect($result['settings'])->firstWhere('id', 'is_active');
    expect($isActive['default'])->toBeTrue();

    $count = collect($result['settings'])->firstWhere('id', 'count');
    expect($count['default'])->toBe(42);
});
