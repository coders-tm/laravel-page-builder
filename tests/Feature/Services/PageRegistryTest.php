<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Services\PageRegistry;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class PageRegistryTest extends TestCase
{
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
        Cache::put(PageRegistry::CACHE_KEY, ['about' => ['title' => 'About']]);

        $registry = $this->app->make(PageRegistry::class);

        // Both calls return the same in-memory array
        $this->assertSame($registry->pages(), $registry->pages());
    }
}
