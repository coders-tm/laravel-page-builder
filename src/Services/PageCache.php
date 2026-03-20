<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Manages HTML caching for rendered pages.
 *
 * Uses a generation counter for O(1) full-cache invalidation without tags,
 * making it compatible with any cache driver (file, database, Redis, etc.).
 */
final class PageCache
{
    private readonly int|false $ttl;

    public function __construct()
    {
        $ttl = (int) config('pagebuilder.cache.ttl', 3600);
        $this->ttl = $ttl === 0 ? false : $ttl;
    }

    /**
     * Whether page HTML caching is enabled.
     */
    public function enabled(): bool
    {
        return (bool) config('pagebuilder.cache.enabled', false);
    }

    /**
     * Return cached HTML for a slug, or null on a miss.
     */
    public function get(string $slug): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $value = Cache::get($this->key($slug));

            return is_string($value) ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Store rendered HTML for a slug.
     */
    public function put(string $slug, string $html): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            if ($this->ttl === false) {
                Cache::forever($this->key($slug), $html);
            } else {
                Cache::put($this->key($slug), $html, $this->ttl);
            }
        } catch (\Throwable) {
            // Cache unavailable — skip silently.
        }
    }

    /**
     * Remove the cached HTML for a single slug.
     */
    public function forget(string $slug): void
    {
        try {
            Cache::forget($this->key($slug));
        } catch (\Throwable) {
            // Ignore.
        }
    }

    /**
     * Invalidate all cached pages by incrementing the generation counter.
     *
     * This is O(1) and works with any cache driver — no tag support required.
     */
    public function flush(): void
    {
        try {
            Cache::increment($this->generationKey());
        } catch (\Throwable) {
            // Ignore.
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function key(string $slug): string
    {
        $prefix = (string) config('pagebuilder.cache.prefix', 'pagebuilder.page');
        $generation = $this->generation();

        return "{$prefix}.{$generation}.{$slug}";
    }

    private function generationKey(): string
    {
        $prefix = (string) config('pagebuilder.cache.prefix', 'pagebuilder.page');

        return "{$prefix}.generation";
    }

    private function generation(): int
    {
        try {
            return (int) Cache::get($this->generationKey(), 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}
