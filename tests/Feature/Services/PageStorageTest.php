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
    $data = [
        'sections' => [
            'banner-1' => [
                'type' => 'banner',
                'settings' => [
                    'text' => 'Welcome Home',
                ],
            ],
            'contact-form_1773542384140' => [
                'type' => 'contact-form',
                'settings' => [],
                'blocks' => [
                    'contact_info_block' => [
                        'type' => 'contact-info',
                        'blocks' => [
                            'item_1' => [
                                'type' => 'item',
                                'settings' => [
                                    'icon' => 'fas fa-location-dot',
                                    'label' => 'Our Location',
                                    'value' => '123 Fitness Street, Muscle City, MC 45678',
                                ],
                            ],
                            'item_2' => [
                                'type' => 'item',
                                'settings' => [
                                    'icon' => 'fas fa-phone',
                                    'label' => 'Phone Number',
                                    'value' => '+1 (555) 123-4567',
                                ],
                            ],
                            'item_3' => [
                                'type' => 'item',
                                'settings' => [
                                    'icon' => 'fas fa-envelope',
                                    'label' => 'Email Address',
                                    'value' => 'info@yourgym.com',
                                ],
                            ],
                            'item_4' => [
                                'type' => 'item',
                                'settings' => [
                                    'icon' => 'fas fa-clock',
                                    'label' => 'Working Hours',
                                    'value' => 'Mon–Fri: 5 AM – 11 PM<br>Sat–Sun: 7 AM – 9 PM',
                                ],
                            ],
                        ],
                        'order' => [
                            'item_1',
                            'item_2',
                            'item_3',
                            'item_4',
                        ],
                    ],
                    'socials_block' => [
                        'type' => 'socials',
                        'blocks' => [
                            'social_1' => [
                                'type' => 'social',
                                'settings' => [
                                    'icon' => 'fa-brands fa-facebook-f',
                                    'url' => '#',
                                ],
                            ],
                            'social_2' => [
                                'type' => 'social',
                                'settings' => [
                                    'icon' => 'fa-brands fa-instagram',
                                    'url' => '#',
                                ],
                            ],
                            'social_3' => [
                                'type' => 'social',
                                'settings' => [
                                    'icon' => 'fa-brands fa-twitter',
                                    'url' => '#',
                                ],
                            ],
                        ],
                        'order' => [
                            'social_1',
                            'social_2',
                            'social_3',
                        ],
                    ],
                ],
                'order' => [
                    'contact_info_block',
                    'socials_block',
                ],
            ],
        ],
        'order' => [
            'banner-1',
            'contact-form_1773542384140',
        ],
        'title' => 'Home Page',
        'meta' => [
            'meta_title' => 'SEO Home',
            'meta_description' => 'Home description',
        ],
    ];

    // 'home' is a preserved page by default
    expect($this->storage->save('home', $data))->toBeTrue();

    $loaded = $this->storage->load('home');
    expect($loaded->title())->toBe('Home Page');
    expect($loaded->meta()['meta_title'])->toBe('SEO Home');
    expect($loaded->meta()['meta_description'])->toBe('Home description');

    // Verify JSON file directly to be absolutely sure
    $filePath = config('pagebuilder.pages').'/home.json';
    $json = json_decode(file_get_contents($filePath), true);
    expect($json)->toHaveKey('title');
    expect($json)->toHaveKey('meta');
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
