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

use PageBuilder\Schema\BlockSchema;
use PageBuilder\Schema\SectionSchema;
use PageBuilder\Schema\SettingSchema;

test('it creates from valid array', function () {
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

    expect($schema->name)->toBe('Hero');
    expect($schema->tag)->toBe('section');
    expect($schema->class)->toBe('hero-section');
    expect($schema->settings)->toHaveCount(1);
    expect($schema->settings[0])->toBeInstanceOf(SettingSchema::class);
    expect($schema->blocks)->toHaveCount(1);
    expect($schema->presets)->toHaveCount(1);
});
test('it throws when name is missing', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("missing required 'name'");

    new SectionSchema([]);
});
test('default values', function () {
    $schema = new SectionSchema(['name' => 'Simple']);

    expect($schema->name)->toBe('Simple');
    expect($schema->tag)->toBe('section');
    expect($schema->class)->toBe('');
    expect($schema->settings)->toBe([]);
    expect($schema->blocks)->toBe([]);
    expect($schema->presets)->toBe([]);

    // limit and maxBlocks default to 0 (int), not null
    expect($schema->limit)->toBe(0);
    expect($schema->maxBlocks)->toBe(0);
});
test('limit and max blocks', function () {
    $schema = new SectionSchema([
        'name' => 'Limited',
        'limit' => 3,
        'max_blocks' => 10,
    ]);

    expect($schema->limit)->toBe(3);
    expect($schema->maxBlocks)->toBe(10);
});
test('setting defaults', function () {
    $schema = new SectionSchema([
        'name' => 'Hero',
        'settings' => [
            ['id' => 'title', 'type' => 'text', 'default' => 'Hello'],
            ['id' => 'subtitle', 'type' => 'text'],
            ['id' => 'show_button', 'type' => 'checkbox', 'default' => true],
        ],
    ]);

    $defaults = $schema->settingDefaults();

    expect($defaults['title'])->toBe('Hello');
    expect($defaults['subtitle'])->toBeNull();
    expect($defaults['show_button'])->toBeTrue();
});
test('accepts theme blocks', function () {
    $schema = new SectionSchema([
        'name' => 'Flexible',
        'blocks' => [['type' => '@theme']],
    ]);

    expect($schema->acceptsThemeBlocks())->toBeTrue();
});
test('does not accept theme blocks', function () {
    $schema = new SectionSchema([
        'name' => 'Fixed',
        'blocks' => [
            ['type' => 'row', 'name' => 'Row'],
        ],
    ]);

    expect($schema->acceptsThemeBlocks())->toBeFalse();
});
test('block schema returns local definition', function () {
    $schema = new SectionSchema([
        'name' => 'Hero',
        'blocks' => [
            ['type' => 'column', 'name' => 'Column', 'settings' => [
                ['id' => 'width', 'type' => 'select', 'default' => 'auto'],
            ]],
        ],
    ]);

    $blockSchema = $schema->blockSchema('column');

    expect($blockSchema)->toBeInstanceOf(BlockSchema::class);
    expect($blockSchema->type)->toBe('column');
    expect($blockSchema->name)->toBe('Column');
    expect($blockSchema->settings)->toHaveCount(1);
});
test('block schema returns null for unknown type', function () {
    $schema = new SectionSchema([
        'name' => 'Hero',
        'blocks' => [
            ['type' => 'row', 'name' => 'Row'],
        ],
    ]);

    expect($schema->blockSchema('unknown'))->toBeNull();
});
test('allowed block types', function () {
    $schema = new SectionSchema([
        'name' => 'Multi',
        'blocks' => [
            ['type' => 'row', 'name' => 'Row'],
            ['type' => 'column', 'name' => 'Column'],
        ],
    ]);

    // allowedBlockTypes contains raw ['type' => '...'] arrays, not plain strings
    expect($schema->allowedBlockTypes)->toBe([['type' => 'row'], ['type' => 'column']]);
});
test('to array includes all fields', function () {
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

    expect($array['name'])->toBe('Hero');
    expect($array['tag'])->toBe('div');
    expect($array['class'])->toBe('hero');
    expect($array['limit'])->toBe(1);
    expect($array['max_blocks'])->toBe(5);
    expect($array['settings'])->toHaveCount(1);
    expect($array['blocks'])->toHaveCount(1);
    expect($array['presets'])->toHaveCount(1);
});
test('json serialization', function () {
    $schema = new SectionSchema([
        'name' => 'Hero',
        'settings' => [
            ['id' => 'title', 'type' => 'text', 'default' => 'Welcome'],
        ],
    ]);

    $json = json_encode($schema);
    $decoded = json_decode($json, true);

    expect($decoded['name'])->toBe('Hero');
    expect($decoded['settings'])->toHaveCount(1);
});
test('inline block definitions create block schemas', function () {
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

    expect($block)->toBeInstanceOf(BlockSchema::class);
    expect($block->name)->toBe('Custom Block');
    expect($block->settings)->toHaveCount(1);
    expect($block->acceptsThemeBlocks())->toBeTrue();
});
test('bare block references are stored as raw arrays', function () {
    $schema = new SectionSchema([
        'name' => 'Section',
        'blocks' => [
            ['type' => 'row'],
        ],
    ]);

    // Bare references (only 'type' key) should not be found as local BlockSchema
    // They need to be resolved from the theme registry at runtime.
    // allowedBlockTypes stores raw ['type' => '...'] arrays.
    expect($schema->allowedBlockTypes)->toBe([['type' => 'row']]);
});
