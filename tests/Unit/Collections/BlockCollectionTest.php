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

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;

test('empty collection', function () {
    $collection = new BlockCollection;

    expect($collection->isEmpty())->toBeTrue();
    expect($collection->isNotEmpty())->toBeFalse();
    expect($collection->count())->toBe(0);
});
test('collection with items', function () {
    $blocks = [
        makeBlock('b1', 'row'),
        makeBlock('b2', 'column'),
    ];

    $collection = new BlockCollection($blocks);

    expect($collection->isEmpty())->toBeFalse();
    expect($collection->isNotEmpty())->toBeTrue();
    expect($collection->count())->toBe(2);
});
test('first and last', function () {
    $blocks = [
        makeBlock('b1', 'row'),
        makeBlock('b2', 'column'),
        makeBlock('b3', 'text'),
    ];

    $collection = new BlockCollection($blocks);

    expect($collection->first()->id)->toBe('b1');
    expect($collection->last()->id)->toBe('b3');
});
test('nth', function () {
    $blocks = [
        makeBlock('b1'),
        makeBlock('b2'),
        makeBlock('b3'),
    ];

    $collection = new BlockCollection($blocks);

    expect($collection->nth(0)->id)->toBe('b1');
    expect($collection->nth(1)->id)->toBe('b2');
    expect($collection->nth(2)->id)->toBe('b3');
    expect($collection->nth(99))->toBeNull();
});
test('find', function () {
    // BlockCollection::find() looks up by string key — items must be keyed by id
    $collection = new BlockCollection([
        'b1' => makeBlock('b1', 'row'),
        'b2' => makeBlock('b2', 'column'),
    ]);

    expect($collection->find('b1')->id)->toBe('b1');
    expect($collection->find('nonexistent'))->toBeNull();
});
test('of type', function () {
    $blocks = [
        makeBlock('b1', 'row'),
        makeBlock('b2', 'column'),
        makeBlock('b3', 'row'),
    ];

    $collection = new BlockCollection($blocks);
    $rows = $collection->ofType('row');

    expect($rows)->toBeInstanceOf(BlockCollection::class);
    expect($rows->count())->toBe(2);
});
test('map', function () {
    $blocks = [
        makeBlock('b1', 'row'),
        makeBlock('b2', 'column'),
    ];

    $collection = new BlockCollection($blocks);
    $ids = $collection->map(fn (Block $block) => $block->id);

    expect($ids)->toBe(['b1', 'b2']);
});
test('iterable', function () {
    $blocks = [
        makeBlock('b1'),
        makeBlock('b2'),
    ];

    $collection = new BlockCollection($blocks);
    $ids = [];

    foreach ($collection as $block) {
        $ids[] = $block->id;
    }

    expect($ids)->toBe(['b1', 'b2']);
});
test('array access', function () {
    $blocks = [
        makeBlock('b1'),
        makeBlock('b2'),
    ];

    $collection = new BlockCollection($blocks);

    expect(isset($collection[0]))->toBeTrue();
    expect($collection[0]->id)->toBe('b1');
    expect(isset($collection[99]))->toBeFalse();
});
test('to array', function () {
    $blocks = [
        makeBlock('b1', 'row'),
    ];

    $collection = new BlockCollection($blocks);
    $array = $collection->toArray();

    expect($array)->toHaveCount(1);
    expect($array[0]['id'])->toBe('b1');
    expect($array[0]['type'])->toBe('row');
});
test('json serialization', function () {
    // BlockCollection is Arrayable but not JsonSerializable.
    // Use toArray() before encoding; items are keyed by id.
    $collection = new BlockCollection([
        'b1' => makeBlock('b1', 'row'),
    ]);

    $json = json_encode($collection->toArray());
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveCount(1);

    // toArray() keys items by id, so the JSON map has 'b1' as the key
    expect($decoded)->toHaveKey('b1');
    expect($decoded['b1']['id'])->toBe('b1');
});
