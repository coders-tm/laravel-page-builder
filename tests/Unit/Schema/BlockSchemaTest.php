<?php

declare(strict_types=1);
use PageBuilder\Schema\BlockSchema;
use PageBuilder\Schema\SettingSchema;

test('it creates from valid array', function () {
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

    expect($schema->type)->toBe('row');
    expect($schema->name)->toBe('Row');
    expect($schema->settings)->toHaveCount(1);
    expect($schema->settings[0])->toBeInstanceOf(SettingSchema::class);
    expect($schema->blocks)->toHaveCount(1);
    expect($schema->presets)->toHaveCount(1);
});
test('it throws when name is missing', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("missing required 'name'");

    new BlockSchema(['type' => 'row']);
});
test('it throws when type is missing', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("missing required 'type'");

    new BlockSchema(['name' => 'Row']);
});
test('it throws when name is empty', function () {
    $this->expectException(InvalidArgumentException::class);

    new BlockSchema(['type' => 'row', 'name' => '']);
});
test('accepts theme blocks', function () {
    $schema = new BlockSchema([
        'type' => 'column',
        'name' => 'Column',
        'blocks' => [['type' => '@theme']],
    ]);

    expect($schema->acceptsThemeBlocks())->toBeTrue();
});
test('does not accept theme blocks without wildcard', function () {
    $schema = new BlockSchema([
        'type' => 'row',
        'name' => 'Row',
        'blocks' => [['type' => 'column']],
    ]);

    expect($schema->acceptsThemeBlocks())->toBeFalse();
});
test('setting defaults', function () {
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

    expect($defaults['columns'])->toBe('2');
    expect($defaults['gap'])->toBe('md');
    expect($defaults['no_default'])->toBeNull();
});
test('to array roundtrip', function () {
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

    expect($array['type'])->toBe('row');
    expect($array['name'])->toBe('Row');
    expect($array['settings'])->toHaveCount(1);
    expect($array['blocks'])->toHaveCount(1);
    expect($array['presets'])->toHaveCount(1);
});
test('json serialization', function () {
    $schema = new BlockSchema([
        'type' => 'column',
        'name' => 'Column',
    ]);

    $json = json_encode($schema);
    $decoded = json_decode($json, true);

    expect($decoded['type'])->toBe('column');
    expect($decoded['name'])->toBe('Column');
});
test('empty settings and blocks', function () {
    $schema = new BlockSchema([
        'type' => 'divider',
        'name' => 'Divider',
    ]);

    expect($schema->settings)->toBe([]);
    expect($schema->blocks)->toBe([]);
    expect($schema->presets)->toBe([]);
    expect($schema->settingDefaults())->toBe([]);
});
