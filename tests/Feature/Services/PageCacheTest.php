<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Services\PageCache;
use Coderstm\PageBuilder\Services\PageStorage;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * All caching tests live here — PageCache internals, invalidation hooks,
 * and end-to-end behaviour through Facades\Page::render().
 */
class PageCacheTest extends TestCase
{
    use RefreshDatabase;

    private PageCache $pageCache;

    private PageStorage $storage;

    // Slugs created in setUp and deleted in tearDown — no committed fixtures needed.
    private const PAGE_A = 'cache-test-page-a';

    private const PAGE_B = 'cache-test-page-b';

    protected function setUp(): void
    {
        parent::setUp();

        $this->pageCache = $this->app->make(PageCache::class);
        $this->storage = $this->app->make(PageStorage::class);

        $this->storage->save(self::PAGE_A, [
            'sections' => [
                'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Page A Content']],
            ],
            'order' => ['banner-1'],
        ]);

        $this->storage->save(self::PAGE_B, [
            'sections' => [
                'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Page B Content']],
            ],
            'order' => ['banner-1'],
        ]);
    }

    protected function tearDown(): void
    {
        $pagesPath = config('pagebuilder.pages');

        @unlink($pagesPath.'/'.self::PAGE_A.'.json');
        @unlink($pagesPath.'/'.self::PAGE_B.'.json');

        parent::tearDown();
    }

    public function test_cache_is_disabled_by_default(): void
    {
        $this->assertFalse($this->pageCache->enabled());
    }

    public function test_cache_is_enabled_when_config_is_true(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->assertTrue($this->app->make(PageCache::class)->enabled());
    }

    public function test_get_returns_null_when_cache_is_disabled(): void
    {
        Cache::shouldReceive('get')->never();

        $this->assertNull($this->pageCache->get('home'));
    }

    public function test_get_returns_null_on_cache_miss(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->assertNull($this->pageCache->get('home'));
    }

    public function test_put_and_get_round_trip(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->put('home', '<h1>Home</h1>');

        $this->assertSame('<h1>Home</h1>', $this->pageCache->get('home'));
    }

    public function test_put_is_a_no_op_when_cache_is_disabled(): void
    {
        Cache::spy();

        $this->pageCache->put('home', '<h1>Home</h1>');

        Cache::shouldNotHaveReceived('put');
        Cache::shouldNotHaveReceived('forever');
    }

    public function test_different_slugs_are_cached_independently(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->put('page-a', '<p>A</p>');
        $this->pageCache->put('page-b', '<p>B</p>');

        $this->assertSame('<p>A</p>', $this->pageCache->get('page-a'));
        $this->assertSame('<p>B</p>', $this->pageCache->get('page-b'));
    }

    public function test_put_stores_forever_when_ttl_is_zero(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);
        $this->app['config']->set('pagebuilder.cache.ttl', 0);

        // Fresh instance so the constructor reads the updated TTL config.
        $cache = new PageCache;

        Cache::spy();
        $cache->put('home', '<h1>Home</h1>');

        Cache::shouldHaveReceived('forever')->once();
        Cache::shouldNotHaveReceived('put');
    }

    public function test_put_uses_configured_ttl_when_nonzero(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);
        $this->app['config']->set('pagebuilder.cache.ttl', 300);

        // Fresh instance so the constructor reads the updated TTL config.
        $cache = new PageCache;

        Cache::spy();
        $cache->put('home', '<h1>Home</h1>');

        Cache::shouldHaveReceived('put')->once()->withArgs(
            fn ($key, $value, $ttl) => $ttl === 300
        );
    }

    public function test_forget_removes_entry_for_given_slug(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->put('about', '<p>About</p>');
        $this->pageCache->forget('about');

        $this->assertNull($this->pageCache->get('about'));
    }

    public function test_forget_does_not_affect_other_slugs(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->put('page-a', '<p>A</p>');
        $this->pageCache->put('page-b', '<p>B</p>');
        $this->pageCache->forget('page-a');

        $this->assertNull($this->pageCache->get('page-a'));
        $this->assertSame('<p>B</p>', $this->pageCache->get('page-b'));
    }

    public function test_flush_invalidates_all_cached_entries(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->put('home', '<h1>Home</h1>');
        $this->pageCache->put('about', '<p>About</p>');
        $this->pageCache->flush();

        $this->assertNull($this->pageCache->get('home'));
        $this->assertNull($this->pageCache->get('about'));
    }

    public function test_new_entries_can_be_stored_after_flush(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->put('home', '<h1>Old</h1>');
        $this->pageCache->flush();
        $this->pageCache->put('home', '<h1>New</h1>');

        $this->assertSame('<h1>New</h1>', $this->pageCache->get('home'));
    }

    public function test_multiple_consecutive_flushes_do_not_break_caching(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->flush();
        $this->pageCache->flush();
        $this->pageCache->put('home', '<p>ok</p>');

        $this->assertSame('<p>ok</p>', $this->pageCache->get('home'));
    }

    public function test_first_render_populates_cache(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->assertNull($this->pageCache->get(self::PAGE_A));

        Page::render(self::PAGE_A);

        $cached = $this->pageCache->get(self::PAGE_A);
        $this->assertNotNull($cached);
        $this->assertStringContainsString('Page A Content', $cached);
    }

    public function test_each_page_slug_gets_its_own_cache_entry(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        Page::render(self::PAGE_A);
        Page::render(self::PAGE_B);

        $this->assertStringContainsString('Page A Content', $this->pageCache->get(self::PAGE_A));
        $this->assertStringContainsString('Page B Content', $this->pageCache->get(self::PAGE_B));
    }

    public function test_second_render_serves_cached_html_not_disk(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        // First render — section HTML is stored in cache.
        Page::render(self::PAGE_A);

        // Overwrite the JSON on disk directly, bypassing PageStorage::save()
        // so the cache is NOT invalidated. Simulates a warm cache with stale disk.
        $diskPath = config('pagebuilder.pages').'/'.self::PAGE_A.'.json';
        file_put_contents($diskPath, json_encode([
            'sections' => [
                'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Updated On Disk']],
            ],
            'order' => ['banner-1'],
        ]));

        // Second render — must return the original cached content, not the disk update.
        $html = Page::render(self::PAGE_A)->render();

        $this->assertStringContainsString('Page A Content', $html);
        $this->assertStringNotContainsString('Updated On Disk', $html);
    }

    public function test_render_does_not_populate_cache_when_disabled(): void
    {
        // Cache disabled by default — nothing should be stored.
        Page::render(self::PAGE_A);

        $this->assertNull($this->pageCache->get(self::PAGE_A));
    }

    public function test_save_clears_cache_for_saved_slug(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        Page::render(self::PAGE_A);
        $this->assertNotNull($this->pageCache->get(self::PAGE_A));

        $this->storage->save(self::PAGE_A, ['sections' => [], 'order' => []]);

        $this->assertNull($this->pageCache->get(self::PAGE_A));
    }

    public function test_save_does_not_clear_cache_for_other_slugs(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        Page::render(self::PAGE_B);
        $this->assertNotNull($this->pageCache->get(self::PAGE_B));

        $this->storage->save(self::PAGE_A, ['sections' => [], 'order' => []]);

        $this->assertNotNull($this->pageCache->get(self::PAGE_B));
    }

    public function test_next_render_re_renders_from_disk_after_save(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        // Warm cache with original content.
        Page::render(self::PAGE_A);

        // Save new content — invalidates cache for PAGE_A only.
        $this->storage->save(self::PAGE_A, [
            'sections' => [
                'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Freshly Saved']],
            ],
            'order' => ['banner-1'],
        ]);

        $html = Page::render(self::PAGE_A)->render();
        $this->assertStringContainsString('Freshly Saved', $html);
    }

    // =========================================================================
    // End-to-end — cache invalidation via ThemeSettings::save()
    // =========================================================================

    public function test_theme_settings_save_flushes_all_page_caches(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        Page::render(self::PAGE_A);
        Page::render(self::PAGE_B);

        $this->assertNotNull($this->pageCache->get(self::PAGE_A));
        $this->assertNotNull($this->pageCache->get(self::PAGE_B));

        $this->app->make(ThemeSettings::class)->save(['primary_color' => '#FF0000']);

        $this->assertNull($this->pageCache->get(self::PAGE_A));
        $this->assertNull($this->pageCache->get(self::PAGE_B));
    }

    public function test_page_re_renders_correctly_after_theme_settings_flush(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        Page::render(self::PAGE_A);
        $this->app->make(ThemeSettings::class)->save(['primary_color' => '#123456']);

        $html = Page::render(self::PAGE_A)->render();

        $this->assertStringContainsString('Page A Content', $html);
    }

    public function test_editor_mode_does_not_read_from_cache(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        // Pre-populate cache with stale content.
        $this->pageCache->put(self::PAGE_A, '<p>stale-editor-cache</p>');

        request()->merge(['pb-editor' => '1']);

        $html = Page::render(self::PAGE_A)->render();

        $this->assertStringNotContainsString('stale-editor-cache', $html);
        $this->assertStringContainsString('Page A Content', $html);
    }

    public function test_editor_mode_does_not_write_to_cache(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        $this->pageCache->flush();

        request()->merge(['pb-editor' => '1']);
        Page::render(self::PAGE_A);

        $this->assertNull($this->pageCache->get(self::PAGE_A));
    }
}
