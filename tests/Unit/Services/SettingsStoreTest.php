<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use PageBuilder\Services\SettingsStore;

beforeEach(function () {
    $this->valuesPath = sys_get_temp_dir().'/pb-settings-store-test.json';
    $this->app['config']->set('pagebuilder.theme_settings_path', $this->valuesPath);

    $this->store = new SettingsStore;
});

afterEach(function () {
    if (File::exists($this->valuesPath)) {
        File::delete($this->valuesPath);
    }
});

test('get returns default when file missing', function () {
    expect($this->store->get('theme', 'primary_color', '#000000'))->toBe('#000000');
});

test('set and get section values', function () {
    expect($this->store->set('theme', ['primary_color' => '#112233']))->toBeTrue();
    expect($this->store->get('theme', 'primary_color'))->toBe('#112233');
    expect($this->store->get('theme'))->toBe(['primary_color' => '#112233']);
});

test('set single key within section', function () {
    $this->store->set('theme', 'font_family', 'Roboto');
    expect($this->store->get('theme', 'font_family'))->toBe('Roboto');
});

test('forget key and section', function () {
    $this->store->set('theme', ['a' => 1, 'b' => 2]);
    $this->store->forget('theme', 'a');

    expect($this->store->get('theme'))->toBe(['b' => 2]);

    $this->store->forget('theme');
    expect($this->store->get('theme'))->toBeNull();
});

test('preserves top-level non-pagebuilder keys', function () {
    File::put($this->valuesPath, json_encode([
        'external_app' => ['mode' => 'dark'],
        '_pagebuilder' => ['theme' => ['color' => 'blue']],
    ]));

    $this->store->set('theme', 'color', 'red');

    $raw = json_decode(File::get($this->valuesPath), true);
    expect($raw['external_app'])->toBe(['mode' => 'dark']);
    expect($raw['_pagebuilder']['theme']['color'])->toBe('red');
});
