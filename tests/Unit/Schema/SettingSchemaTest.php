<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\Schema\SettingSchema;

test('it creates from array', function () {
    $schema = new SettingSchema([
        'id' => 'title',
        'type' => 'text',
        'label' => 'Title',
        'default' => 'Hello',
    ]);

    expect($schema->id)->toBe('title');
    expect($schema->type)->toBe('text');
    expect($schema->label)->toBe('Title');
    expect($schema->default)->toBe('Hello');
});
test('it defaults type to text', function () {
    $schema = new SettingSchema(['id' => 'name']);

    expect($schema->type)->toBe('text');
});
test('it handles missing optional fields', function () {
    $schema = new SettingSchema([]);

    expect($schema->id)->toBeNull();
    expect($schema->type)->toBe('text');
    expect($schema->label)->toBeNull();
    expect($schema->default)->toBeNull();
    expect($schema->info)->toBeNull();
    expect($schema->placeholder)->toBeNull();
    expect($schema->content)->toBeNull();
    expect($schema->options)->toBeNull();
    expect($schema->min)->toBeNull();
    expect($schema->max)->toBeNull();
    expect($schema->step)->toBeNull();
    expect($schema->unit)->toBeNull();
});
test('it parses range fields', function () {
    $schema = new SettingSchema([
        'id' => 'opacity',
        'type' => 'range',
        'min' => '0',
        'max' => '100',
        'step' => '5',
        'unit' => '%',
    ]);

    expect($schema->min)->toBe(0);
    expect($schema->max)->toBe(100);
    expect($schema->step)->toBe(5);
    expect($schema->unit)->toBe('%');
});
test('it parses select options', function () {
    $options = [
        ['label' => 'Small', 'value' => 'sm'],
        ['label' => 'Large', 'value' => 'lg'],
    ];

    $schema = new SettingSchema([
        'id' => 'size',
        'type' => 'select',
        'options' => $options,
    ]);

    expect($schema->options)->toBe($options);
});
test('to array excludes nulls', function () {
    $schema = new SettingSchema([
        'id' => 'title',
        'type' => 'text',
        'label' => 'Title',
        'default' => 'Hello',
    ]);

    $array = $schema->toArray();

    expect($array)->toHaveKey('id');
    expect($array)->toHaveKey('type');
    expect($array)->toHaveKey('label');
    expect($array)->toHaveKey('default');
    $this->assertArrayNotHasKey('info', $array);
    $this->assertArrayNotHasKey('placeholder', $array);
    $this->assertArrayNotHasKey('min', $array);
});
test('json serialization', function () {
    $schema = new SettingSchema([
        'id' => 'bg',
        'type' => 'color',
        'default' => '#fff',
    ]);

    $json = json_encode($schema);
    $decoded = json_decode($json, true);

    expect($decoded['id'])->toBe('bg');
    expect($decoded['type'])->toBe('color');
    expect($decoded['default'])->toBe('#fff');
});
