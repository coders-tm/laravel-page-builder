<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Http;

use Coderstm\PageBuilder\Http\Controllers\WebPageController;
use Coderstm\PageBuilder\Http\Middleware\RequestThemeMiddleware;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

/**
 * Verifies that ?theme= query parameter routes each request to the correct
 * theme's page JSON.
 *
 * Two fixture themes are created in a temp directory:
 *   alpha  — shop.json renders "Alpha Shop Content"
 *   beta   — shop.json renders "Beta Shop Content"
 *
 * The RequestThemeMiddleware (registered globally by PageBuilderServiceProvider)
 * intercepts the ?theme= parameter and calls Theme::set(), which updates
 * config('pagebuilder.pages') for the lifetime of that request.
 */
class MultiThemePageTest extends TestCase
{
    use RefreshDatabase;

    private string $themeBase;

    protected function defineRoutes($router): void
    {
        $router->middleware([RequestThemeMiddleware::class])
            ->get('/shop', [WebPageController::class, 'pages'])
            ->defaults('slug', 'shop');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeBase = sys_get_temp_dir().'/pb-multi-theme-http-'.uniqid();

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

    // ─── Fresh rendering ──────────────────────────────────────────────────────

    public function test_second_request_for_same_theme_reflects_disk_updates(): void
    {
        $this->get('/shop?theme=alpha')->assertSee('ALPHA Shop Content');

        File::put("{$this->themeBase}/alpha/views/pages/shop.json", json_encode([
            'sections' => [
                'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Alpha Updated On Disk']],
            ],
            'order' => ['banner-1'],
        ]));

        $this->get('/shop?theme=alpha')
            ->assertOk()
            ->assertSee('Alpha Updated On Disk')
            ->assertDontSee('ALPHA Shop Content');
    }
}
