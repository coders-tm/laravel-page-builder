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
use PageBuilder\PageBuilder;
use PageBuilder\Services\PageStorage;
use PageBuilder\Support\PageData;

beforeEach(function () {
    $this->storage = $this->app->make(PageStorage::class);
    $this->pagesPath = config('pagebuilder.pages');
});

afterEach(function () {
    PageBuilder::setLang(null);

    // Clean up test files
    File::delete($this->pagesPath.'/lang-test.json');
    File::delete($this->pagesPath.'/lang-test.fr.json');
    File::delete($this->pagesPath.'/lang-test.de.json');
    File::delete($this->pagesPath.'/lang-test-only-default.json');
    File::delete($this->pagesPath.'/lang-test-only-default.fr.json');
});

test('load falls back to default when lang is set but locale file does not exist', function () {
    // Create only the default file
    $data = [
        'sections' => ['hero' => ['type' => 'hero', 'settings' => [], 'blocks' => [], 'order' => []]],
        'order' => ['hero'],
    ];
    file_put_contents($this->pagesPath.'/lang-test-only-default.json', json_encode($data));

    PageBuilder::setLang('fr');

    $loaded = $this->storage->load('lang-test-only-default');
    expect($loaded)->toBeInstanceOf(PageData::class);
    expect($loaded->order())->toBe(['hero']);
});

test('load reads from locale-specific file when it exists', function () {
    // Create default file
    $defaultData = [
        'sections' => ['hero' => ['type' => 'hero', 'settings' => ['title' => 'Default'], 'blocks' => [], 'order' => []]],
        'order' => ['hero'],
    ];
    file_put_contents($this->pagesPath.'/lang-test.json', json_encode($defaultData));

    // Create French file with different content
    $frData = [
        'sections' => ['hero' => ['type' => 'hero', 'settings' => ['title' => 'French'], 'blocks' => [], 'order' => []]],
        'order' => ['hero'],
    ];
    file_put_contents($this->pagesPath.'/lang-test.fr.json', json_encode($frData));

    PageBuilder::setLang('fr');

    $loaded = $this->storage->load('lang-test');
    expect($loaded)->toBeInstanceOf(PageData::class);
    expect($loaded->section('hero')['settings']['title'])->toBe('French');
});

test('load uses default when lang is null', function () {
    $defaultData = [
        'sections' => ['hero' => ['type' => 'hero', 'settings' => ['title' => 'Default'], 'blocks' => [], 'order' => []]],
        'order' => ['hero'],
    ];
    file_put_contents($this->pagesPath.'/lang-test.json', json_encode($defaultData));

    $frData = [
        'sections' => ['hero' => ['type' => 'hero', 'settings' => ['title' => 'French'], 'blocks' => [], 'order' => []]],
        'order' => ['hero'],
    ];
    file_put_contents($this->pagesPath.'/lang-test.fr.json', json_encode($frData));

    // No lang set — should read default
    $loaded = $this->storage->load('lang-test');
    expect($loaded->section('hero')['settings']['title'])->toBe('Default');
});

test('load returns null when both locale and default files are missing', function () {
    PageBuilder::setLang('fr');

    expect($this->storage->load('nonexistent-page'))->toBeNull();
});

test('loadRaw falls back to default when locale file missing', function () {
    $data = [
        'sections' => ['s' => ['type' => 'hero', 'settings' => [], 'blocks' => [], 'order' => []]],
        'order' => ['s'],
    ];
    file_put_contents($this->pagesPath.'/lang-test.json', json_encode($data));

    PageBuilder::setLang('fr');

    $raw = $this->storage->loadRaw('lang-test');
    expect($raw)->toBeArray();
    expect($raw['order'])->toBe(['s']);
});

test('loadRaw reads from locale file when it exists', function () {
    $frData = [
        'sections' => ['s' => ['type' => 'hero', 'settings' => ['lang' => 'fr'], 'blocks' => [], 'order' => []]],
        'order' => ['s'],
    ];
    file_put_contents($this->pagesPath.'/lang-test.fr.json', json_encode($frData));

    PageBuilder::setLang('fr');

    $raw = $this->storage->loadRaw('lang-test');
    expect($raw)->toBeArray();
    expect($raw['sections']['s']['settings']['lang'])->toBe('fr');
});

test('save writes to locale-specific path when lang is set', function () {
    PageBuilder::setLang('fr');

    $data = [
        'sections' => ['hero' => ['type' => 'hero', 'settings' => [], 'blocks' => [], 'order' => []]],
        'order' => ['hero'],
    ];

    expect($this->storage->save('lang-test', $data))->toBeTrue();

    // French file should exist
    expect(File::exists($this->pagesPath.'/lang-test.fr.json'))->toBeTrue();

    // Default file should not exist
    expect(File::exists($this->pagesPath.'/lang-test.json'))->toBeFalse();

    // Verify content
    $json = json_decode(File::get($this->pagesPath.'/lang-test.fr.json'), true);
    expect($json['order'])->toBe(['hero']);
});

test('save writes to default path when lang is null', function () {
    $data = [
        'sections' => ['hero' => ['type' => 'hero', 'settings' => [], 'blocks' => [], 'order' => []]],
        'order' => ['hero'],
    ];

    expect($this->storage->save('lang-test', $data))->toBeTrue();

    // Default file should exist
    expect(File::exists($this->pagesPath.'/lang-test.json'))->toBeTrue();

    // French file should not exist
    expect(File::exists($this->pagesPath.'/lang-test.fr.json'))->toBeFalse();
});

test('save and load roundtrip with lang set', function () {
    PageBuilder::setLang('fr');

    $data = [
        'sections' => [
            'hero' => [
                'type' => 'hero',
                'settings' => ['title' => 'Bonjour'],
                'blocks' => [],
                'order' => [],
            ],
        ],
        'order' => ['hero'],
    ];

    expect($this->storage->save('lang-test', $data))->toBeTrue();

    $loaded = $this->storage->load('lang-test');
    expect($loaded)->toBeInstanceOf(PageData::class);
    expect($loaded->section('hero')['settings']['title'])->toBe('Bonjour');
});

test('save to different langs creates separate files', function () {
    PageBuilder::setLang('fr');
    $this->storage->save('lang-test', [
        'sections' => ['s' => ['type' => 'hero', 'settings' => ['lang' => 'fr'], 'blocks' => [], 'order' => []]],
        'order' => ['s'],
    ]);

    PageBuilder::setLang('de');
    $this->storage->save('lang-test', [
        'sections' => ['s' => ['type' => 'hero', 'settings' => ['lang' => 'de'], 'blocks' => [], 'order' => []]],
        'order' => ['s'],
    ]);

    expect(File::exists($this->pagesPath.'/lang-test.fr.json'))->toBeTrue();
    expect(File::exists($this->pagesPath.'/lang-test.de.json'))->toBeTrue();

    // Load French
    PageBuilder::setLang('fr');
    $fr = $this->storage->load('lang-test');
    expect($fr->section('s')['settings']['lang'])->toBe('fr');

    // Load German
    PageBuilder::setLang('de');
    $de = $this->storage->load('lang-test');
    expect($de->section('s')['settings']['lang'])->toBe('de');
});

test('switching lang does not affect default file', function () {
    // Save default
    $this->storage->save('lang-test', [
        'sections' => ['s' => ['type' => 'hero', 'settings' => ['lang' => 'default'], 'blocks' => [], 'order' => []]],
        'order' => ['s'],
    ]);

    // Save French
    PageBuilder::setLang('fr');
    $this->storage->save('lang-test', [
        'sections' => ['s' => ['type' => 'hero', 'settings' => ['lang' => 'fr'], 'blocks' => [], 'order' => []]],
        'order' => ['s'],
    ]);

    // Default should still have default content
    PageBuilder::setLang(null);
    $default = $this->storage->load('lang-test');
    expect($default->section('s')['settings']['lang'])->toBe('default');
});
