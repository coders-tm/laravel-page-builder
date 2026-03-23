<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Services\PageRegistry;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class PageRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset static key so other tests are not affected.
        PageBuilder::$pageCacheKey = 'pagebuilder.pages';

        parent::tearDown();
    }

    public function test_pages_returns_empty_when_no_cache(): void
    {
        $registry = $this->app->make(PageRegistry::class);

        $this->assertSame([], $registry->pages());
    }

    public function test_pages_loads_from_cache_file(): void
    {
        $registry = $this->app->make(PageRegistry::class);
        $registry->put([
            'about' => ['title' => 'About', 'slug' => 'about', 'path' => '/pages/about.json'],
        ]);

        $pages = $registry->pages();

        $this->assertCount(1, $pages);
        $this->assertArrayHasKey('about', $pages);
    }

    public function test_page_returns_specific_page(): void
    {
        $registry = $this->app->make(PageRegistry::class);
        $registry->put([
            'about' => ['title' => 'About', 'slug' => 'about'],
        ]);

        $page = $registry->page('about');
        $this->assertIsArray($page);
        $this->assertSame('About', $page['title']);
    }

    public function test_page_returns_null_for_missing_slug(): void
    {
        $registry = $this->app->make(PageRegistry::class);

        $this->assertNull($registry->page('nonexistent'));
    }

    public function test_load_pages_is_cached(): void
    {
        Cache::put(PageBuilder::$pageCacheKey, ['about' => ['title' => 'About']]);

        $registry = $this->app->make(PageRegistry::class);

        // Both calls return the same in-memory array
        $this->assertSame($registry->pages(), $registry->pages());
    }

    public function test_custom_cache_key_isolates_registry_from_default(): void
    {
        // Populate under the default key.
        $default = $this->app->make(PageRegistry::class);
        $default->put(['home' => ['title' => 'Default Home']]);

        // Switch to a tenant-specific key.
        PageBuilder::$pageCacheKey = 'pagebuilder.pages.tenant-99';

        // New instance reads from the new key — must be empty.
        $tenant = new PageRegistry;

        $this->assertSame([], $tenant->pages());

        // Populate under the tenant key.
        $tenant->put(['shop' => ['title' => 'Tenant Shop']]);

        // Switch back to the default key and verify it is unchanged.
        PageBuilder::$pageCacheKey = 'pagebuilder.pages';
        $reloaded = new PageRegistry;

        $this->assertArrayHasKey('home', $reloaded->pages());
        $this->assertArrayNotHasKey('shop', $reloaded->pages());
    }

    public function test_flush_only_clears_current_key(): void
    {
        // Populate default.
        $default = $this->app->make(PageRegistry::class);
        $default->put(['home' => ['title' => 'Home']]);

        // Populate tenant.
        PageBuilder::$pageCacheKey = 'pagebuilder.pages.tenant-99';
        $tenant = new PageRegistry;
        $tenant->put(['shop' => ['title' => 'Shop']]);

        // Flush only the tenant key.
        $tenant->flush();

        $this->assertSame([], $tenant->pages());
        $this->assertNotNull(Cache::get('pagebuilder.pages'));
    }
}
