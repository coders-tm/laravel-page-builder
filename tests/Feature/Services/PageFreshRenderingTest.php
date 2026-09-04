<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use PageBuilder\Facades\Page;
use PageBuilder\Services\PageStorage;

const PAGE_A = 'fresh-rendering-page-a';
const PAGE_B = 'fresh-rendering-page-b';
const SHARED_PAGE = 'shared-variable-rendering-test-page';
const SHARED_SECTION = 'shared-variable-rendering-test';
beforeEach(function () {
    $this->storage = $this->app->make(PageStorage::class);

    $this->storage->save(PAGE_A, [
        'sections' => [
            'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Page A Content']],
        ],
        'order' => ['banner-1'],
    ]);

    $this->storage->save(PAGE_B, [
        'sections' => [
            'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Page B Content']],
        ],
        'order' => ['banner-1'],
    ]);

    File::put(config('pagebuilder.sections').'/'.SHARED_SECTION.'.blade.php', <<<'BLADE'
@schema([
    'name' => 'Shared Variable Rendering Test',
    'settings' => [],
])

<div class="shared-variable">{{ $sharedRenderingValue ?? 'missing' }}</div>
BLADE);
});
afterEach(function () {
    $pagesPath = config('pagebuilder.pages');

    @unlink($pagesPath.'/'.PAGE_A.'.json');
    @unlink($pagesPath.'/'.PAGE_B.'.json');
    @unlink($pagesPath.'/'.SHARED_PAGE.'.json');
    @unlink($pagesPath.'/'.PAGE_A.'.blade.php');
    @unlink($pagesPath.'/'.PAGE_B.'.blade.php');
    @unlink(config('pagebuilder.sections').'/'.SHARED_SECTION.'.blade.php');
    View::share('sharedRenderingValue', null);

});
test('render does not create generated blade view', function () {
    $viewPath = config('pagebuilder.pages').'/'.PAGE_A.'.blade.php';

    $this->assertFileDoesNotExist($viewPath);

    $html = Page::render(PAGE_A)->render();

    $this->assertFileDoesNotExist($viewPath);
    $this->assertStringContainsString('Page A Content', $html);
});
test('render reads updated json between requests', function () {
    $firstHtml = Page::render(PAGE_A)->render();

    File::put(config('pagebuilder.pages').'/'.PAGE_A.'.json', json_encode([
        'sections' => [
            'banner-1' => ['type' => 'banner', 'settings' => ['text' => 'Page A Updated']],
        ],
        'order' => ['banner-1'],
    ]));

    $secondHtml = Page::render(PAGE_A)->render();

    $this->assertStringContainsString('Page A Content', $firstHtml);
    $this->assertStringContainsString('Page A Updated', $secondHtml);
    $this->assertStringNotContainsString('Page A Content', $secondHtml);
});
test('shared view data changes are reflected between renders', function () {
    $this->storage->save(SHARED_PAGE, [
        'sections' => [
            'shared-1' => ['type' => SHARED_SECTION, 'settings' => []],
        ],
        'order' => ['shared-1'],
    ]);

    View::share('sharedRenderingValue', 'First value');
    $firstHtml = Page::render(SHARED_PAGE)->render();

    View::share('sharedRenderingValue', 'Second value');
    $secondHtml = Page::render(SHARED_PAGE)->render();

    $this->assertStringContainsString('First value', $firstHtml);
    $this->assertStringContainsString('Second value', $secondHtml);
    $this->assertStringNotContainsString('First value', $secondHtml);
});
