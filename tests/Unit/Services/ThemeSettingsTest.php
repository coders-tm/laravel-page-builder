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

use Illuminate\Support\Facades\File;
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

    $this->themeSettings = app(ThemeSettings::class);
    $this->themeSettings->flush();
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

test('typed accessors work correctly', function () {
    $this->themeSettings->save([
        'title' => 'PageBuilder',
        'items_count' => '42',
        'ratio' => '1.618',
        'is_active' => 'true',
        'tags' => ['php', 'laravel'],
    ]);

    expect($this->themeSettings->getString('title'))->toBe('PageBuilder');
    expect($this->themeSettings->getInt('items_count'))->toBe(42);
    expect($this->themeSettings->getFloat('ratio'))->toBe(1.618);
    expect($this->themeSettings->getBool('is_active'))->toBeTrue();
    expect($this->themeSettings->getArray('tags'))->toBe(['php', 'laravel']);
    expect($this->themeSettings->has('title'))->toBeTrue();
    expect($this->themeSettings->has('non_existing'))->toBeFalse();
});

test('set and setMany persist changes', function () {
    $this->themeSettings->set('primary_color', '#123456');
    expect($this->themeSettings->getString('primary_color'))->toBe('#123456');

    $this->themeSettings->setMany(['secondary_color' => '#654321', 'radius' => 8]);
    expect($this->themeSettings->getString('secondary_color'))->toBe('#654321');
    expect($this->themeSettings->getInt('radius'))->toBe(8);
});

test('array access interface works', function () {
    $this->themeSettings['theme_mode'] = 'dark';

    expect(isset($this->themeSettings['theme_mode']))->toBeTrue();
    expect($this->themeSettings['theme_mode'])->toBe('dark');

    unset($this->themeSettings['theme_mode']);
    expect(isset($this->themeSettings['theme_mode']))->toBeFalse();
});

test('magic get returns saved value', function () {
    $this->themeSettings->save(['primary_color' => '#00FF00', 'font_family' => 'serif']);

    expect($this->themeSettings->primary_color)->toBe('#00FF00');
    expect($this->themeSettings->font_family)->toBe('serif');
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

    $fresh = app(ThemeSettings::class);
    $fresh->flush();

    expect($fresh->values())->toBe(['primary_color' => '#ABCDEF']);
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

test('fontElements returns empty string when no google_font settings exist', function () {
    expect($this->themeSettings->fontElements())->toBe('');
});

test('fontElements returns google font link tags based on schema and saved values', function () {
    $this->app['config']->set('pagebuilder.theme_settings_schema', [
        [
            'name' => 'Typography',
            'settings' => [
                ['key' => 'body_font', 'type' => 'google_font', 'default' => 'Inter'],
                ['key' => 'heading_font', 'type' => 'google_font', 'default' => 'Roboto'],
            ],
        ],
    ]);

    $this->themeSettings->save(['body_font' => 'Poppins']);

    $html = $this->themeSettings->fontElements();

    expect($html)->toContain('https://fonts.googleapis.com/css2?');
    expect($html)->toContain('family=Poppins');
    expect($html)->toContain('family=Roboto');
    expect($html)->toContain('<link rel="preconnect" href="https://fonts.googleapis.com">');
});
