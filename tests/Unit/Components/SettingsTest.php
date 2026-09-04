<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\Components\Settings;
use PageBuilder\Schema\SettingSchema;

test('get returns value', function () {
    $settings = new Settings(
        ['title' => 'Hello', 'color' => '#fff'],
        []
    );

    expect($settings->get('title'))->toBe('Hello');
    expect($settings->get('color'))->toBe('#fff');
});
test('get falls back to default', function () {
    $settings = new Settings(
        [],
        ['title' => 'Default Title']
    );

    expect($settings->get('title'))->toBe('Default Title');
});
test('get returns explicit fallback', function () {
    $settings = new Settings([], []);

    expect($settings->get('missing', 'fallback'))->toBe('fallback');
});
test('has checks values and defaults', function () {
    $settings = new Settings(
        ['title' => 'Hello'],
        ['subtitle' => 'Default']
    );

    expect($settings->has('title'))->toBeTrue();
    expect($settings->has('subtitle'))->toBeTrue();
    expect($settings->has('nonexistent'))->toBeFalse();
});
test('all merges defaults with values', function () {
    $settings = new Settings(
        ['title' => 'Custom'],
        ['title' => 'Default', 'subtitle' => 'Default Sub']
    );

    $all = $settings->all();
    expect($all['title'])->toBe('Custom');
    expect($all['subtitle'])->toBe('Default Sub');
});
test('raw returns only explicit values', function () {
    $settings = new Settings(
        ['title' => 'Hello'],
        ['title' => 'Default', 'subtitle' => 'Sub']
    );

    $raw = $settings->raw();
    expect($raw)->toBe(['title' => 'Hello']);
});
test('defaults returns only defaults', function () {
    $settings = new Settings(
        ['title' => 'Hello'],
        ['title' => 'Default', 'subtitle' => 'Sub']
    );

    $defaults = $settings->defaults();
    expect($defaults)->toBe(['title' => 'Default', 'subtitle' => 'Sub']);
});
test('magic get', function () {
    $settings = new Settings(['title' => 'Hello'], []);

    expect($settings->title)->toBe('Hello');
});
test('magic isset', function () {
    $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

    expect(isset($settings->title))->toBeTrue();
    expect(isset($settings->color))->toBeTrue();
    expect(isset($settings->missing))->toBeFalse();
});
test('invokable', function () {
    $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

    expect($settings('title'))->toBe('Hello');
    expect($settings('color'))->toBe('#000');
    expect($settings('missing', 'fallback'))->toBe('fallback');
});
test('to string', function () {
    $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

    expect((string) $settings)->toBe('{"color":"#000","title":"Hello"}');
});
test('array access', function () {
    $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

    expect(isset($settings['title']))->toBeTrue();
    expect($settings['title'])->toBe('Hello');
    expect($settings['color'])->toBe('#000');
});
test('array access set throws', function () {
    $settings = new Settings([], []);

    $settings['title'] = 'value';
})->throws(\BadMethodCallException::class, 'Settings is immutable');
test('array access unset throws', function () {
    $settings = new Settings(['title' => 'Hello'], []);

    unset($settings['title']);
})->throws(\BadMethodCallException::class, 'Settings is immutable');
test('to array', function () {
    $settings = new Settings(
        ['title' => 'Custom'],
        ['title' => 'Default', 'subtitle' => 'Sub']
    );

    $array = $settings->toArray();
    expect($array['title'])->toBe('Custom');
    expect($array['subtitle'])->toBe('Sub');
});
test('json serializable', function () {
    $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);
    $json = json_encode($settings);
    $decoded = json_decode($json, true);

    expect($decoded['title'])->toBe('Hello');
    expect($decoded['color'])->toBe('#000');
});
test('from schema', function () {
    $schemas = [
        new SettingSchema(['id' => 'title', 'type' => 'text', 'default' => 'Hello']),
        new SettingSchema(['id' => 'color', 'type' => 'color', 'default' => '#000']),
    ];

    $settings = Settings::fromSchema(['title' => 'Custom'], $schemas);

    expect($settings->get('title'))->toBe('Custom');
    expect($settings->get('color'))->toBe('#000');
    expect($settings->defaults())->toBe(['title' => 'Hello', 'color' => '#000']);
});
