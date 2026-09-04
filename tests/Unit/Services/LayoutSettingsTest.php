<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\File;
use PageBuilder\Services\LayoutSettings;
use PageBuilder\Support\LayoutConfig;

beforeEach(function () {
    $this->valuesPath = sys_get_temp_dir().'/pb-layout-settings-test.json';

    $this->app['config']->set('pagebuilder.theme_settings_path', $this->valuesPath);

    $this->layoutSettings = app(LayoutSettings::class);
    $this->layoutSettings->flush();
});

afterEach(function () {
    if (File::exists($this->valuesPath)) {
        File::delete($this->valuesPath);
    }
});

test('all returns empty array when no file exists', function () {
    expect($this->layoutSettings->all())->toBe([]);
});

test('get returns empty array for missing layout type', function () {
    expect($this->layoutSettings->get('page'))->toBe([]);
});

test('has checks for existence of layout type', function () {
    expect($this->layoutSettings->has('page'))->toBeFalse();

    $this->layoutSettings->save('page', ['header' => [], 'footer' => []]);

    expect($this->layoutSettings->has('page'))->toBeTrue();
});

test('save persists layout config array to disk', function () {
    $config = [
        'header' => [
            'sections' => [
                'announcement' => [
                    'type' => 'announcement',
                    'settings' => ['text' => 'Flash Sale!'],
                    'blocks' => [],
                    'order' => [],
                ],
            ],
            'order' => ['announcement'],
        ],
        'footer' => [
            'sections' => [],
            'order' => [],
        ],
    ];

    expect($this->layoutSettings->save('page', $config))->toBeTrue();
    expect($this->valuesPath)->toBeFile();

    $raw = json_decode(File::get($this->valuesPath), true);
    expect($raw['_pagebuilder']['layouts']['page'])->toBe($config);
});

test('save accepts LayoutConfig instance', function () {
    $dto = LayoutConfig::fromArray([
        'header' => [
            'sections' => ['hero' => ['type' => 'hero']],
            'order' => ['hero'],
        ],
    ]);

    expect($this->layoutSettings->save('landing', $dto))->toBeTrue();

    $saved = $this->layoutSettings->getConfig('landing');
    expect($saved)->toBeInstanceOf(LayoutConfig::class);
    expect($saved->headerOrder())->toBe(['hero']);
});

test('get returns saved layout config', function () {
    $config = [
        'header' => [
            'sections' => [
                'header' => [
                    'type' => 'header',
                    'settings' => ['logo' => '/img/logo.png'],
                    'blocks' => [],
                    'order' => [],
                ],
            ],
            'order' => ['header'],
        ],
        'footer' => [
            'sections' => [],
            'order' => [],
        ],
    ];

    $this->layoutSettings->save('page', $config);

    expect($this->layoutSettings->get('page'))->toBe($config);
});

test('all returns all layout configs', function () {
    $pageConfig = [
        'header' => ['sections' => [], 'order' => []],
        'footer' => ['sections' => [], 'order' => []],
    ];
    $simpleConfig = [
        'header' => ['sections' => [], 'order' => []],
        'footer' => ['sections' => [], 'order' => []],
    ];

    $this->layoutSettings->save('page', $pageConfig);
    $this->layoutSettings->save('simple', $simpleConfig);

    $all = $this->layoutSettings->all();
    expect($all)->toHaveCount(2);
    expect($all)->toHaveKey('page');
    expect($all)->toHaveKey('simple');
});

test('delete removes layout config', function () {
    $config = [
        'header' => ['sections' => [], 'order' => []],
        'footer' => ['sections' => [], 'order' => []],
    ];

    $this->layoutSettings->save('page', $config);
    expect($this->layoutSettings->get('page'))->not->toBe([]);

    expect($this->layoutSettings->delete('page'))->toBeTrue();
    expect($this->layoutSettings->get('page'))->toBe([]);
});

test('delete returns true for non-existent layout', function () {
    expect($this->layoutSettings->delete('nonexistent'))->toBeTrue();
});

test('save preserves other keys in settings.json', function () {
    File::put($this->valuesPath, json_encode([
        '_pagebuilder' => [
            'theme' => ['colors.primary' => '#FF0000'],
        ],
        'other_key' => 'keep-me',
    ]));

    $config = [
        'header' => ['sections' => [], 'order' => []],
        'footer' => ['sections' => [], 'order' => []],
    ];

    $this->layoutSettings->save('page', $config);

    $raw = json_decode(File::get($this->valuesPath), true);
    expect($raw['_pagebuilder']['theme'])->toBe(['colors.primary' => '#FF0000']);
    expect($raw['other_key'])->toBe('keep-me');
    expect($raw['_pagebuilder']['layouts']['page'])->toBe($config);
});

test('flush invalidates cache', function () {
    $config = [
        'header' => ['sections' => [], 'order' => []],
        'footer' => ['sections' => [], 'order' => []],
    ];

    $this->layoutSettings->save('page', $config);
    $this->layoutSettings->get('page');

    File::put($this->valuesPath, json_encode([
        '_pagebuilder' => [
            'layouts' => [
                'page' => [
                    'header' => ['sections' => [], 'order' => []],
                    'footer' => ['sections' => [], 'order' => []],
                ],
            ],
        ],
    ]));

    $this->layoutSettings->flush();
    expect($this->layoutSettings->get('page'))->toBeArray();
});
