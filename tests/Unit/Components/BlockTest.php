<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;

test('construction with full data', function () {
    $block = new Block([
        'id' => 'block-1',
        'type' => 'row',
        'name' => 'Row Block',
        'disabled' => false,
        'settings' => new Settings(['columns' => '2'], []),
        'blocks' => new BlockCollection,
    ]);

    expect($block->id)->toBe('block-1');
    expect($block->type)->toBe('row');
    expect($block->name)->toBe('Row Block');
    expect($block->disabled)->toBeFalse();
    expect($block->settings->columns)->toBe('2');
});
test('default type is block', function () {
    $block = new Block([
        'id' => 'block-1',
    ]);

    expect($block->type)->toBe('block');
});
test('disabled block', function () {
    $block = new Block([
        'id' => 'block-1',
        'type' => 'row',
        'disabled' => true,
    ]);

    expect($block->disabled)->toBeTrue();
});
test('editor attributes empty when editor disabled', function () {
    PageBuilder::disableEditor();

    $block = new Block([
        'id' => 'block-1',
        'type' => 'row',
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);

    expect($block->editorAttributes())->toBe('');
});
test('nested blocks', function () {
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

    expect($parentBlock->blocks)->toHaveCount(1);
    expect($parentBlock->blocks->first()->id)->toBe('col-1');
});
test('to array', function () {
    $block = new Block([
        'id' => 'block-1',
        'type' => 'row',
        'name' => 'Row',
        'settings' => new Settings(['columns' => '3'], []),
        'blocks' => new BlockCollection,
    ]);

    $array = $block->toArray();

    expect($array['id'])->toBe('block-1');
    expect($array['type'])->toBe('row');
    expect($array['name'])->toBe('Row');
    expect($array['settings']['columns'])->toBe('3');
});
test('json serialization', function () {
    $block = new Block([
        'id' => 'block-1',
        'type' => 'column',
        'name' => 'Column',
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);

    $json = json_encode($block);
    $decoded = json_decode($json, true);

    expect($decoded['id'])->toBe('block-1');
    expect($decoded['type'])->toBe('column');
});
afterEach(function () {
    PageBuilder::disableEditor();
});
