<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Services\PageStorage;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class PageViewCacheTest extends TestCase
{
    use RefreshDatabase;

    private PageStorage $storage;

    private const PAGE_A = 'cache-test-page-a';

    private const PAGE_B = 'cache-test-page-b';

    protected function setUp(): void
    {
        parent::setUp();

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
        @unlink($pagesPath.'/'.self::PAGE_A.'.blade.php');
        @unlink($pagesPath.'/'.self::PAGE_B.'.blade.php');

        parent::tearDown();
    }

    public function test_first_render_creates_blade_view_when_cache_enabled(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);
        $viewPath = config('pagebuilder.pages').'/'.self::PAGE_A.'.blade.php';

        $this->assertFileDoesNotExist($viewPath);

        Page::render(self::PAGE_A);

        $this->assertFileExists($viewPath);
        $content = File::get($viewPath);
        $this->assertStringContainsString('[builder-generated]', $content);
        $this->assertStringContainsString('Page A Content', $content);
    }

    public function test_render_uses_cached_blade_view(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);
        $viewPath = config('pagebuilder.pages').'/'.self::PAGE_A.'.blade.php';

        // Pre-populate cache with specific content
        $this->storage->saveView(self::PAGE_A, '<h1>Cached Content</h1>');

        $html = Page::render(self::PAGE_A)->render();

        $this->assertStringContainsString('<h1>Cached Content</h1>', $html);
        $this->assertStringNotContainsString('Page A Content', $html);
    }

    public function test_save_deletes_cached_blade_view(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);
        $viewPath = config('pagebuilder.pages').'/'.self::PAGE_A.'.blade.php';

        Page::render(self::PAGE_A);
        $this->assertFileExists($viewPath);

        $this->storage->save(self::PAGE_A, ['sections' => [], 'order' => []]);

        $this->assertFileDoesNotExist($viewPath);
    }

    public function test_theme_settings_save_flushes_all_generated_views(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);
        $pathA = config('pagebuilder.pages').'/'.self::PAGE_A.'.blade.php';
        $pathB = config('pagebuilder.pages').'/'.self::PAGE_B.'.blade.php';

        Page::render(self::PAGE_A);
        Page::render(self::PAGE_B);

        $this->assertFileExists($pathA);
        $this->assertFileExists($pathB);

        $this->app->make(ThemeSettings::class)->save(['primary_color' => '#FF0000']);

        $this->assertFileDoesNotExist($pathA);
        $this->assertFileDoesNotExist($pathB);
    }

    public function test_custom_user_views_are_not_flushed_by_theme_settings(): void
    {
        $pagesPath = config('pagebuilder.pages');
        $customPath = $pagesPath.'/custom.blade.php';

        File::put($customPath, '<h1>Custom User View</h1>');

        $this->app->make(ThemeSettings::class)->save(['primary_color' => '#FF0000']);

        $this->assertFileExists($customPath);
        @unlink($customPath);
    }

    public function test_editor_mode_bypasses_and_does_not_create_cache(): void
    {
        $this->app['config']->set('pagebuilder.cache.enabled', true);
        $viewPath = config('pagebuilder.pages').'/'.self::PAGE_A.'.blade.php';

        request()->merge(['pb-editor' => '1']);

        $html = Page::render(self::PAGE_A)->render();

        $this->assertFileDoesNotExist($viewPath);
        $this->assertStringContainsString('Page A Content', $html);
    }
}
