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
use PageBuilder\Services\TemplateStorage;

beforeEach(function () {
    $this->storage = $this->app->make(TemplateStorage::class);
});
test('loads default page template', function () {
    $data = $this->storage->load('page');

    expect($data)->toBeArray();
    expect($data)->toHaveKey('sections');
    expect($data)->toHaveKey('order');
    expect($data['order'])->toBe(['main']);
});
test('loads alternate template', function () {
    $data = $this->storage->load('page.alternate');

    expect($data)->toBeArray();
    expect($data)->toHaveKey('wrapper');
    expect($data['wrapper'])->toBe('main#page-alternate.page-wrapper');
});
test('returns null for missing template', function () {
    expect($this->storage->load('nonexistent'))->toBeNull();
});
test('normalizes name with json extension', function () {
    $data = $this->storage->load('page.json');

    expect($data)->toBeArray();
    expect($data)->toHaveKey('sections');
});
test('normalizes empty name to page', function () {
    // Empty string should fall back to 'page'
    $data = $this->storage->load('');

    expect($data)->toBeArray();
    expect($data)->toHaveKey('sections');
});
test('returns null for invalid json', function () {
    $path = config('pagebuilder.templates').'/broken.json';
    file_put_contents($path, 'not valid json');

    expect($this->storage->load('broken'))->toBeNull();

    File::delete($path);
});
test('template sections contain expected type', function () {
    $data = $this->storage->load('page');

    expect($data['sections']['main']['type'])->toBe('page-content');
});
test('loads variable template', function () {
    $data = $this->storage->load('page.var');

    expect($data)->toBeArray();
    expect($data)->toHaveKey('sections');

    $settings = $data['sections']['title-banner']['settings'] ?? [];
    expect($settings['text'])->toBe('{{ $page->title }}');
});
test('loads templates from configured templates path', function () {
    $customPath = sys_get_temp_dir().'/pb_test_templates_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/custom.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content']],
            'order' => ['main'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        $storage = new TemplateStorage;
        $data = $storage->load('custom');

        expect($data)->toBeArray();
        expect($data['order'])->toBe(['main']);
        expect($data['sections']['main']['type'])->toBe('page-content');
    } finally {
        File::deleteDirectory($customPath);
    }
});
test('all returns templates from configured path', function () {
    $customPath = sys_get_temp_dir().'/pb_test_templates_all_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/my-layout.json', json_encode([
            'sections' => [],
            'order' => [],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        $storage = new TemplateStorage;
        $templates = $storage->all();

        $values = array_column($templates, 'value');
        expect($values)->toContain('my-layout');
    } finally {
        File::deleteDirectory($customPath);
    }
});
test('returns null for template absent from configured path and theme', function () {
    // 'nonexistent-xyz' does not exist in the workbench templates or the theme,
    // so load() must return null regardless of the configured path.
    expect($this->storage->load('nonexistent-xyz'))->toBeNull();
});
test('configured templates path is used before fallback', function () {
    // Verify that a template only present in the custom path IS loaded,
    // confirming the configured path is consulted.
    $customPath = sys_get_temp_dir().'/pb_test_templates_primary_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/only-in-custom.json', json_encode([
            'sections' => ['s' => ['type' => 'hero']],
            'order' => ['s'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        $storage = new TemplateStorage;
        $data = $storage->load('only-in-custom');

        expect($data)->toBeArray();
        expect($data['order'])->toBe(['s']);
    } finally {
        File::deleteDirectory($customPath);
    }
});
