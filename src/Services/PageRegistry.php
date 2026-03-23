<?php

namespace Coderstm\PageBuilder\Services;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\PageBuilder;
use Illuminate\Support\Facades\Cache;

class PageRegistry
{
    /**
     * Pages data loaded once at construction (singleton).
     */
    protected array $pages;

    public function __construct()
    {
        try {
            if (Cache::has(PageBuilder::$pageCacheKey)) {
                $this->pages = Cache::get(PageBuilder::$pageCacheKey);

                return;
            }
        } catch (\Throwable) {
            // Cache driver might not be available or table might be missing
        }

        try {
            $this->pages = $this->buildFromDatabase();
            Cache::put(PageBuilder::$pageCacheKey, $this->pages);
        } catch (\Throwable) {
            // DB not available yet (e.g. before migrations). Don't cache so
            // the next request retries automatically.
            $this->pages = [];
        }
    }

    /**
     * Query the database and build the pages array.
     * Delegates to PageService so the model-access logic lives in one place.
     */
    private function buildFromDatabase(): array
    {
        return Page::allActive();
    }

    /**
     * Get all registered pages.
     */
    public function pages(): array
    {
        return $this->pages;
    }

    /**
     * Get a specific page by slug.
     */
    public function page(string $slug): ?array
    {
        return $this->pages[$slug] ?? null;
    }

    /**
     * Replace the in-memory and cached pages data.
     * Called by RegeneratePages after building the registry from the DB.
     */
    public function put(array $pages): void
    {
        try {
            Cache::forever(PageBuilder::$pageCacheKey, $pages);
        } catch (\Throwable) {
            // Cache not available
        }
        $this->pages = $pages;
    }

    /**
     * Flush the cache and immediately rebuild from the database.
     * Use this when you need an up-to-date registry in the same process
     * (e.g. after seeding or in Artisan commands).
     */
    public function reload(): void
    {
        try {
            Cache::forget(PageBuilder::$pageCacheKey);
        } catch (\Throwable) {
            // Cache not available
        }
        $this->pages = $this->buildFromDatabase();
        try {
            Cache::forever(PageBuilder::$pageCacheKey, $this->pages);
        } catch (\Throwable) {
            // Cache not available
        }
    }

    /**
     * Clear the cached pages so the next request re-populates from the DB.
     */
    public function flush(): void
    {
        try {
            Cache::forget(PageBuilder::$pageCacheKey);
        } catch (\Throwable) {
            // Cache not available
        }
        $this->pages = [];
    }
}
