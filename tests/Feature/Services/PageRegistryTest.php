<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use PageBuilder\PageBuilder;
use PageBuilder\Services\PageRegistry;

afterEach(function () {
    // Reset static key so other tests are not affected.
    PageBuilder::$pageCacheKey = 'pagebuilder.pages';

});
test('pages returns empty when no cache', function () {
    $registry = $this->app->make(PageRegistry::class);

    expect($registry->pages())->toBe([]);
});
test('pages loads from cache file', function () {
    $registry = $this->app->make(PageRegistry::class);
    $registry->put([
        'about' => ['title' => 'About', 'slug' => 'about', 'path' => '/pages/about.json'],
    ]);

    $pages = $registry->pages();

    expect($pages)->toHaveCount(1);
    expect($pages)->toHaveKey('about');
});
test('page returns specific page', function () {
    $registry = $this->app->make(PageRegistry::class);
    $registry->put([
        'about' => ['title' => 'About', 'slug' => 'about'],
    ]);

    $page = $registry->page('about');
    expect($page)->toBeArray();
    expect($page['title'])->toBe('About');
});
test('page returns null for missing slug', function () {
    $registry = $this->app->make(PageRegistry::class);

    expect($registry->page('nonexistent'))->toBeNull();
});
test('load pages is cached', function () {
    Cache::put(PageBuilder::$pageCacheKey, ['about' => ['title' => 'About']]);

    $registry = $this->app->make(PageRegistry::class);

    // Both calls return the same in-memory array
    expect($registry->pages())->toBe($registry->pages());
});
test('custom cache key isolates registry from default', function () {
    // Populate under the default key.
    $default = $this->app->make(PageRegistry::class);
    $default->put(['home' => ['title' => 'Default Home']]);

    // Switch to a tenant-specific key.
    PageBuilder::$pageCacheKey = 'pagebuilder.pages.tenant-99';

    // New instance reads from the new key — must be empty.
    $tenant = new PageRegistry;

    expect($tenant->pages())->toBe([]);

    // Populate under the tenant key.
    $tenant->put(['shop' => ['title' => 'Tenant Shop']]);

    // Switch back to the default key and verify it is unchanged.
    PageBuilder::$pageCacheKey = 'pagebuilder.pages';
    $reloaded = new PageRegistry;

    expect($reloaded->pages())->toHaveKey('home');
    $this->assertArrayNotHasKey('shop', $reloaded->pages());
});
test('flush only clears current key', function () {
    // Populate default.
    $default = $this->app->make(PageRegistry::class);
    $default->put(['home' => ['title' => 'Home']]);

    // Populate tenant.
    PageBuilder::$pageCacheKey = 'pagebuilder.pages.tenant-99';
    $tenant = new PageRegistry;
    $tenant->put(['shop' => ['title' => 'Shop']]);

    // Flush only the tenant key.
    $tenant->flush();

    expect($tenant->pages())->toBe([]);
    expect(Cache::get('pagebuilder.pages'))->not->toBeNull();
});
