<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use PageBuilder\Services\PageStorage;
use PageBuilder\Services\ThemeSettings;

beforeEach(function () {
    $this->valuesPath = sys_get_temp_dir().'/pb-theme-settings-test.json';

    $this->app['config']->set('pagebuilder.theme_settings_path', $this->valuesPath);
    $this->app['config']->set('pagebuilder.theme_settings_schema', [
        [
            'name' => 'Colors',
            'settings' => [
                ['id' => 'primary_color', 'type' => 'color', 'label' => 'Primary', 'default' => '#3B82F6'],
                ['id' => 'font_family',   'type' => 'select', 'label' => 'Font', 'default' => 'sans'],
            ],
        ],
    ]);

    // Fresh instance so it picks up the config set above
    $this->themeSettings = new ThemeSettings($this->app->make(PageStorage::class));
});
afterEach(function () {
    if (File::exists($this->valuesPath)) {
        File::delete($this->valuesPath);
    }

});
test('get returns null when no values file exists', function () {
    expect($this->themeSettings->get('primary_color'))->toBeNull();
});
test('get returns default when key missing', function () {
    expect($this->themeSettings->get('primary_color', '#3B82F6'))->toBe('#3B82F6');
});
test('get returns saved value', function () {
    $this->themeSettings->save(['primary_color' => '#FF0000']);

    expect($this->themeSettings->get('primary_color'))->toBe('#FF0000');
});
test('get returns default when key not in saved values', function () {
    $this->themeSettings->save(['primary_color' => '#FF0000']);

    expect($this->themeSettings->get('missing_key', 'fallback'))->toBe('fallback');
});
test('magic get returns null for missing key', function () {
    expect($this->themeSettings->primary_color)->toBeNull();
});
test('magic get returns saved value', function () {
    $this->themeSettings->save(['primary_color' => '#00FF00', 'font_family' => 'serif']);

    expect($this->themeSettings->primary_color)->toBe('#00FF00');
    expect($this->themeSettings->font_family)->toBe('serif');
});
test('magic get returns null for unknown key', function () {
    $this->themeSettings->save(['primary_color' => '#000']);

    expect($this->themeSettings->unknown_key)->toBeNull();
});
test('save persists values to disk', function () {
    $values = ['primary_color' => '#123456', 'font_family' => 'mono'];

    expect($this->themeSettings->save($values))->toBeTrue();
    expect($this->valuesPath)->toBeFile();

    $raw = json_decode(File::get($this->valuesPath), true);
    expect($raw['_pagebuilder']['theme'])->toBe($values);
});
test('values are loaded from disk', function () {
    File::put($this->valuesPath, json_encode(['_pagebuilder' => ['theme' => ['primary_color' => '#ABCDEF']]]));

    $fresh = new ThemeSettings($this->app->make(PageStorage::class));
    expect($fresh->values())->toBe(['primary_color' => '#ABCDEF']);
});
test('values returns empty if pagebuilder key missing', function () {
    File::put($this->valuesPath, json_encode(['primary_color' => '#FEDCBA']));

    $fresh = new ThemeSettings($this->app->make(PageStorage::class));
    expect($fresh->values())->toBe([]);
});
test('values cache is refreshed after save', function () {
    $this->themeSettings->save(['primary_color' => '#111111']);
    $this->themeSettings->save(['primary_color' => '#222222']);

    expect($this->themeSettings->get('primary_color'))->toBe('#222222');
});
test('values returns empty array when no file', function () {
    expect($this->themeSettings->values())->toBe([]);
});
test('schema returns config schema', function () {
    $schema = $this->themeSettings->schema();

    expect($schema)->toHaveCount(1);
    expect($schema[0]['name'])->toBe('Colors');
    expect($schema[0]['settings'])->toHaveCount(2);
});
test('to array contains schema and values keys', function () {
    $this->themeSettings->save(['primary_color' => '#FF00FF']);

    $result = $this->themeSettings->toArray();

    expect($result)->toHaveKey('schema');
    expect($result)->toHaveKey('values');
    expect($result['values'])->toBe(['primary_color' => '#FF00FF']);
});
test('save preserves other keys', function () {
    File::put($this->valuesPath, json_encode([
        'other_setting' => 'keep-me',
        '_pagebuilder' => [
            'theme' => ['primary_color' => '#000000'],
            'layouts' => ['page' => ['header' => [], 'footer' => []]],
        ],
    ]));

    $this->themeSettings->save(['primary_color' => '#FFFFFF']);

    $raw = json_decode(File::get($this->valuesPath), true);
    expect($raw['other_setting'])->toBe('keep-me');
    expect($raw['_pagebuilder']['layouts']['page'])->toBe(['header' => [], 'footer' => []]);
    expect($raw['_pagebuilder']['theme']['primary_color'])->toBe('#FFFFFF');
});
