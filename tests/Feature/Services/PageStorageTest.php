<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use PageBuilder\Services\LayoutSettings;
use PageBuilder\Services\PageStorage;
use PageBuilder\Support\PageData;

beforeEach(function () {
    $this->storage = $this->app->make(PageStorage::class);
    $this->layoutSettings = $this->app->make(LayoutSettings::class);
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
    expect($loaded->meta()->isEmpty())->toBeTrue();

    // Verify JSON file directly
    $filePath = config('pagebuilder.pages').'/regular-page.json';
    $json = json_decode(file_get_contents($filePath), true);
    $this->assertArrayNotHasKey('title', $json);
    $this->assertArrayNotHasKey('meta', $json);
});

// ── Layout splitting tests ──────────────────────────────────────────────

test('new page saves layout to layout settings when no existing page json', function () {
    $data = [
        'sections' => [
            'hero' => ['type' => 'hero', 'settings' => [], 'blocks' => [], 'order' => []],
        ],
        'order' => ['hero'],
        'layout' => [
            'type' => 'page',
            'header' => [
                'sections' => [
                    'header' => ['type' => 'header', 'settings' => ['logo' => '/logo.png'], 'blocks' => [], 'order' => []],
                ],
                'order' => ['header'],
            ],
            'footer' => [
                'sections' => [
                    'footer' => ['type' => 'footer', 'settings' => ['copyright' => '2026'], 'blocks' => [], 'order' => []],
                ],
                'order' => ['footer'],
            ],
        ],
    ];

    expect($this->storage->save('new-page-layout', $data))->toBeTrue();

    // Page JSON should have layout as string only
    $filePath = config('pagebuilder.pages').'/new-page-layout.json';
    $json = json_decode(file_get_contents($filePath), true);
    expect($json['layout'])->toBe('page');
    expect($json['layout'])->not->toBeArray();

    // LayoutSettings should have the full config
    $sharedLayout = $this->layoutSettings->get('page');
    expect($sharedLayout)->toHaveKey('header');
    expect($sharedLayout)->toHaveKey('footer');
    expect($sharedLayout['header']['sections']['header']['settings']['logo'])->toBe('/logo.png');
    expect($sharedLayout['footer']['sections']['footer']['settings']['copyright'])->toBe('2026');
});

test('page with existing string layout saves to layout settings', function () {
    // First, create a page with string layout
    $this->storage->save('string-existing', [
        'sections' => [],
        'order' => [],
        'layout' => 'page',
    ]);

    // Now save with full layout object — should go to LayoutSettings
    $data = [
        'sections' => [],
        'order' => [],
        'layout' => [
            'type' => 'page',
            'header' => [
                'sections' => [
                    'header' => ['type' => 'header', 'settings' => ['logo' => '/new.png'], 'blocks' => [], 'order' => []],
                ],
                'order' => ['header'],
            ],
            'footer' => ['sections' => [], 'order' => []],
        ],
    ];

    expect($this->storage->save('string-existing', $data))->toBeTrue();

    // Page JSON should still have layout as string
    $filePath = config('pagebuilder.pages').'/string-existing.json';
    $json = json_decode(file_get_contents($filePath), true);
    expect($json['layout'])->toBe('page');
    expect($json['layout'])->not->toBeArray();

    // LayoutSettings should have the config
    $sharedLayout = $this->layoutSettings->get('page');
    expect($sharedLayout['header']['sections']['header']['settings']['logo'])->toBe('/new.png');
});

test('page with existing object layout saves to page json', function () {
    // First, create a page with object layout by writing directly to disk
    $filePath = config('pagebuilder.pages').'/object-existing.json';
    $initialData = [
        'sections' => [],
        'order' => [],
        'layout' => [
            'type' => 'page',
            'header' => [
                'sections' => [
                    'header' => ['type' => 'header', 'settings' => ['logo' => '/old.png'], 'blocks' => [], 'order' => []],
                ],
                'order' => ['header'],
            ],
            'footer' => ['sections' => [], 'order' => []],
        ],
    ];
    file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));

    // Now save with updated layout — should go to page.json since existing is object
    $data = [
        'sections' => [],
        'order' => [],
        'layout' => [
            'type' => 'page',
            'header' => [
                'sections' => [
                    'header' => ['type' => 'header', 'settings' => ['logo' => '/updated.png'], 'blocks' => [], 'order' => []],
                ],
                'order' => ['header'],
            ],
            'footer' => ['sections' => [], 'order' => []],
        ],
    ];

    expect($this->storage->save('object-existing', $data))->toBeTrue();

    // Page JSON should have full layout object (without source)
    $json = json_decode(file_get_contents($filePath), true);
    expect($json['layout'])->toBeArray();
    expect($json['layout']['type'])->toBe('page');
    expect($json['layout']['header']['sections']['header']['settings']['logo'])->toBe('/updated.png');
});

test('missing layout defaults to page type and saves to layout settings', function () {
    $data = [
        'sections' => [],
        'order' => [],
        // no layout key
    ];

    expect($this->storage->save('no-layout', $data))->toBeTrue();

    // Page JSON should have layout as string
    $filePath = config('pagebuilder.pages').'/no-layout.json';
    $json = json_decode(file_get_contents($filePath), true);
    expect($json['layout'])->toBe('page');
});

test('layout source key is stripped from layout settings', function () {
    $data = [
        'sections' => [],
        'order' => [],
        'layout' => [
            'type' => 'page',
            'source' => 'shared',
            'header' => ['sections' => [], 'order' => []],
            'footer' => ['sections' => [], 'order' => []],
        ],
    ];

    expect($this->storage->save('strip-source-settings', $data))->toBeTrue();

    // LayoutSettings should not have source key
    $sharedLayout = $this->layoutSettings->get('page');
    expect($sharedLayout)->not->toHaveKey('source');
    expect($sharedLayout)->toHaveKey('header');
    expect($sharedLayout)->toHaveKey('footer');
});

test('layout source key is stripped from page json when existing is object', function () {
    // First, create a page with object layout by writing directly to disk
    $filePath = config('pagebuilder.pages').'/strip-source-page.json';
    $initialData = [
        'sections' => [],
        'order' => [],
        'layout' => [
            'type' => 'page',
            'header' => ['sections' => [], 'order' => []],
            'footer' => ['sections' => [], 'order' => []],
        ],
    ];
    file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));

    // Now save with source key — should be stripped
    $data = [
        'sections' => [],
        'order' => [],
        'layout' => [
            'type' => 'page',
            'source' => 'page',
            'header' => ['sections' => [], 'order' => []],
            'footer' => ['sections' => [], 'order' => []],
        ],
    ];

    expect($this->storage->save('strip-source-page', $data))->toBeTrue();

    // Page JSON should not have source key
    $json = json_decode(file_get_contents($filePath), true);
    expect($json['layout'])->not->toHaveKey('source');
});
