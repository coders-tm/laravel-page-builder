<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use PageBuilder\Services\PageStorage;
use PageBuilder\Support\PageData;

beforeEach(function () {
    $this->storage = $this->app->make(PageStorage::class);
});
test('save and load', function () {
    $data = [
        'sections' => [
            'hero' => [
                'type' => 'hero',
                'settings' => ['title' => 'Hello'],
                'blocks' => [],
                'order' => [],
            ],
        ],
        'order' => ['hero'],
        'title' => 'Test Page',
    ];

    expect($this->storage->save('test-page', $data))->toBeTrue();

    $loaded = $this->storage->load('test-page');
    expect($loaded)->toBeInstanceOf(PageData::class);

    // title is a DB-only field — stripped on save, not present in JSON
    expect($loaded->title())->toBe('');
    expect($loaded->order())->toBe(['hero']);
});
test('load returns null for missing page', function () {
    expect($this->storage->load('nonexistent'))->toBeNull();
});
test('save with page data object', function () {
    $pageData = PageData::fromArray([
        'sections' => [],
        'order' => [],
        'title' => 'From PageData',
    ]);

    expect($this->storage->save('from-object', $pageData))->toBeTrue();

    $loaded = $this->storage->load('from-object');

    // title is a DB-only field — not persisted to JSON
    expect($loaded->title())->toBe('');
});
test('save overwrites existing', function () {
    $this->storage->save('overwrite', [
        'sections' => [],
        'order' => [],
        'title' => 'First',
    ]);

    $this->storage->save('overwrite', [
        'sections' => [],
        'order' => [],
        'title' => 'Second',
    ]);

    $loaded = $this->storage->load('overwrite');

    // title is a DB-only field — not persisted to JSON; verify sections are overwritten
    expect($loaded->title())->toBe('');
    expect($loaded->order())->toBe([]);
});
test('load returns null for invalid json', function () {
    $path = config('pagebuilder.pages').'/invalid.json';
    file_put_contents($path, 'not valid json');

    expect($this->storage->load('invalid'))->toBeNull();

    // Cleanup
    if (File::exists($path)) {
        File::delete($path);
    }
});
test('preserved page persists title and meta', function () {
    $filePath = config('pagebuilder.pages').'/home.json';
    $originalContent = file_get_contents($filePath);

    try {
        $data = json_decode($originalContent, true);
        $data['title'] = 'Home Page';
        $data['meta'] = [
            'meta_title' => 'SEO Home',
            'meta_description' => 'Home description',
        ];

        // 'home' is a preserved page by default
        expect($this->storage->save('home', $data))->toBeTrue();

        $loaded = $this->storage->load('home');
        expect($loaded->title())->toBe('Home Page');
        expect($loaded->meta()['meta_title'])->toBe('SEO Home');
        expect($loaded->meta()['meta_description'])->toBe('Home description');

        // Verify JSON file directly to be absolutely sure
        $json = json_decode(file_get_contents($filePath), true);
        expect($json)->toHaveKey('title');
        expect($json)->toHaveKey('meta');
    } finally {
        file_put_contents($filePath, $originalContent);
    }
});
test('regular page strips title and meta', function () {
    $data = [
        'sections' => [],
        'order' => [],
        'title' => 'Regular Page',
        'meta' => [
            'meta_title' => 'SEO Regular',
        ],
    ];

    expect($this->storage->save('regular-page', $data))->toBeTrue();

    $loaded = $this->storage->load('regular-page');
    expect($loaded->title())->toBe('');
    expect($loaded->meta())->toBe([]);

    // Verify JSON file directly
    $filePath = config('pagebuilder.pages').'/regular-page.json';
    $json = json_decode(file_get_contents($filePath), true);
    $this->assertArrayNotHasKey('title', $json);
    $this->assertArrayNotHasKey('meta', $json);
});
