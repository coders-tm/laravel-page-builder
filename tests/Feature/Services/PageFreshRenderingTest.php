<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Services\PageStorage;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class PageFreshRenderingTest extends TestCase
{
    use RefreshDatabase;

    private PageStorage $storage;

    private const PAGE_A = 'fresh-rendering-page-a';

    private const PAGE_B = 'fresh-rendering-page-b';

    private const SHARED_PAGE = 'shared-variable-rendering-test-page';

    private const SHARED_SECTION = 'shared-variable-rendering-test';

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

        File::put(config('pagebuilder.sections').'/'.self::SHARED_SECTION.'.blade.php', <<<'BLADE'
@schema([
    'name' => 'Shared Variable Rendering Test',
    'settings' => [],
])

<div class="shared-variable">{{ $sharedRenderingValue ?? 'missing' }}</div>
BLADE);
    }

    protected function tearDown(): void
    {
        $pagesPath = config('pagebuilder.pages');

        @unlink($pagesPath.'/'.self::PAGE_A.'.json');
        @unlink($pagesPath.'/'.self::PAGE_B.'.json');
        @unlink($pagesPath.'/'.self::SHARED_PAGE.'.json');
        @unlink($pagesPath.'/'.self::PAGE_A.'.blade.php');
        @unlink($pagesPath.'/'.self::PAGE_B.'.blade.php');
        @unlink(config('pagebuilder.sections').'/'.self::SHARED_SECTION.'.blade.php');
        View::share('sharedRenderingValue', null);

        parent::tearDown();
    }

    public function test_render_does_not_create_generated_blade_view(): void
    {
        $viewPath = config('pagebuilder.pages').'/'.self::PAGE_A.'.blade.php';

        $this->assertFileDoesNotExist($viewPath);

        $html = Page::render(self::PAGE_A)->render();

        $this->assertFileDoesNotExist($viewPath);
        $this->assertStringContainsString('Page A Content', $html);
    }

    public function test_render_reads_updated_json_between_requests(): void
    {
        $firstHtml = Page::render(self::PAGE_A)->render();

        File::put(config('pagebuilder.pages').'/'.self::PAGE_A.'.json', json_encode([
            'sections' => [
                'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Page A Updated']],
            ],
            'order' => ['banner-1'],
        ]));

        $secondHtml = Page::render(self::PAGE_A)->render();

        $this->assertStringContainsString('Page A Content', $firstHtml);
        $this->assertStringContainsString('Page A Updated', $secondHtml);
        $this->assertStringNotContainsString('Page A Content', $secondHtml);
    }

    public function test_shared_view_data_changes_are_reflected_between_renders(): void
    {
        $this->storage->save(self::SHARED_PAGE, [
            'sections' => [
                'shared-1' => ['type' => self::SHARED_SECTION, 'settings' => []],
            ],
            'order' => ['shared-1'],
        ]);

        View::share('sharedRenderingValue', 'First value');
        $firstHtml = Page::render(self::SHARED_PAGE)->render();

        View::share('sharedRenderingValue', 'Second value');
        $secondHtml = Page::render(self::SHARED_PAGE)->render();

        $this->assertStringContainsString('First value', $firstHtml);
        $this->assertStringContainsString('Second value', $secondHtml);
        $this->assertStringNotContainsString('First value', $secondHtml);
    }
}
