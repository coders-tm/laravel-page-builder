<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Http;

use Coderstm\PageBuilder\Http\Controllers\WebPageController;
use Coderstm\PageBuilder\Services\PageCache;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

/**
 * Verifies that ?theme= query parameter routes each request to the correct
 * theme's page JSON, and that per-theme caches do not bleed into each other.
 *
 * Two fixture themes are created in a temp directory:
 *   alpha  — shop.json renders "Alpha Shop Content"
 *   beta   — shop.json renders "Beta Shop Content"
 *
 * The RequestThemeMiddleware (registered globally by PageBuilderServiceProvider)
 * intercepts the ?theme= parameter and calls Theme::set(), which updates both
 * config('pagebuilder.pages') and config('pagebuilder.cache.prefix') for the
 * lifetime of that request.
 */
class MultiThemePageTest extends TestCase
{
    use RefreshDatabase;

    private string $themeBase;

    private PageCache $pageCache;

    protected function defineRoutes($router): void
    {
        $router->get('/shop', [WebPageController::class, 'pages'])
            ->defaults('slug', 'shop');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeBase = sys_get_temp_dir().'/pb-multi-theme-http-'.uniqid();
        $this->pageCache = $this->app->make(PageCache::class);

        foreach (['alpha', 'beta'] as $theme) {
            $pagesDir = "{$this->themeBase}/{$theme}/views/pages";
            File::makeDirectory($pagesDir, 0755, true);

            File::put("{$pagesDir}/shop.json", json_encode([
                'sections' => [
                    'banner-1' => [
                        'type' => 'banner',
                        'settings' => ['text' => strtoupper($theme).' Shop Content'],
                    ],
                ],
                'order' => ['banner-1'],
            ]));
        }

        $this->app['config']->set('theme.base_path', $this->themeBase);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themeBase);

        parent::tearDown();
    }

    // ─── Basic content routing ────────────────────────────────────────────────

    public function test_theme_alpha_returns_alpha_page_content(): void
    {
        $this->get('/shop?theme=alpha')
            ->assertOk()
            ->assertSee('ALPHA Shop Content');
    }

    public function test_theme_beta_returns_beta_page_content(): void
    {
        $this->get('/shop?theme=beta')
            ->assertOk()
            ->assertSee('BETA Shop Content');
    }

    public function test_each_theme_returns_its_own_content_not_the_others(): void
    {
        $this->get('/shop?theme=alpha')
            ->assertOk()
            ->assertSee('ALPHA Shop Content')
            ->assertDontSee('BETA Shop Content');

        $this->get('/shop?theme=beta')
            ->assertOk()
            ->assertSee('BETA Shop Content')
            ->assertDontSee('ALPHA Shop Content');
    }

    // ─── Cache isolation ──────────────────────────────────────────────────────

    public function test_cached_alpha_response_is_not_served_for_beta_request(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        // Warm the cache for alpha.
        $this->get('/shop?theme=alpha')->assertSee('ALPHA Shop Content');

        // Beta must render fresh from its own JSON, not the alpha cache.
        $this->get('/shop?theme=beta')
            ->assertOk()
            ->assertSee('BETA Shop Content')
            ->assertDontSee('ALPHA Shop Content');
    }

    public function test_cached_beta_response_is_not_served_for_alpha_request(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        // Warm the cache for beta.
        $this->get('/shop?theme=beta')->assertSee('BETA Shop Content');

        // Alpha must render fresh from its own JSON, not the beta cache.
        $this->get('/shop?theme=alpha')
            ->assertOk()
            ->assertSee('ALPHA Shop Content')
            ->assertDontSee('BETA Shop Content');
    }

    public function test_second_request_for_same_theme_is_served_from_cache(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);

        // First request — populates cache.
        $this->get('/shop?theme=alpha')->assertSee('ALPHA Shop Content');

        // Overwrite the JSON on disk directly, bypassing PageStorage::save()
        // so the cache entry is NOT invalidated. Simulates a warm cache.
        File::put("{$this->themeBase}/alpha/views/pages/shop.json", json_encode([
            'sections' => [
                'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Alpha Updated On Disk']],
            ],
            'order' => ['banner-1'],
        ]));

        // Second request — must return the cached version, not the disk update.
        $this->get('/shop?theme=alpha')
            ->assertOk()
            ->assertSee('ALPHA Shop Content')
            ->assertDontSee('Alpha Updated On Disk');
    }
}
