<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\Registry\BlockRegistry;
use PageBuilder\Schema\BlockSchema;

beforeEach(function () {
    $this->registry = $this->app->make(BlockRegistry::class);
});
test('auto discovers blocks from path', function () {
    expect($this->registry->has('row'))->toBeTrue();

    $entry = $this->registry->get('row');
    expect($entry)->toBeArray();
    expect($entry['schema'])->toBeInstanceOf(BlockSchema::class);
    expect($entry['schema']->name)->toBe('Row');
    expect($entry['schema']->type)->toBe('row');
});
test('prepare raw schema injects type', function () {
    $entry = $this->registry->get('column');
    expect($entry['schema']->type)->toBe('column');
});
test('types returns all registered', function () {
    $types = $this->registry->types();
    expect($types)->toContain('row');
    expect($types)->toContain('column');
});
test('has returns false for unregistered', function () {
    expect($this->registry->has('nonexistent'))->toBeFalse();
});
test('register manual block', function () {
    $schema = new BlockSchema([
        'type' => 'text',
        'name' => 'Text Block',
    ]);

    $this->registry->register('text', $schema);

    expect($this->registry->has('text'))->toBeTrue();
    $entry = $this->registry->get('text');
    expect($entry['schema']->name)->toBe('Text Block');
});
test('skips blade files without schema', function () {
    expect($this->registry->has('non-schema'))->toBeFalse();
});
