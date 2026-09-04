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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use PageBuilder\Facades\Page;

test('custom view takes precedence over editor mode', function () {
    // 1. Create a physical Blade view in the workbench
    $viewPath = resource_path('views/pages/about-static.blade.php');
    @mkdir(dirname($viewPath), 0755, true);
    file_put_contents($viewPath, 'Custom Static Content');

    try {
        // 2. Set up editor mode query param
        $request = Request::create('/about-static', 'GET', ['pb-editor' => 'true']);
        $this->app->instance('request', $request);

        // 3. Render the page
        $response = Page::render('about-static');

        // 4. Assert it rendered the custom view, not the editor-ready page
        expect($response)->toBeInstanceOf(Illuminate\View\View::class);
        expect($response->name())->toEqual('pages.about-static');

        $html = $response->render();
        $this->assertStringContainsString('Custom Static Content', $html);

        // It should NOT contain editor attributes if it's the static view
        $this->assertStringNotContainsString('data-pb-section', $html);
    } finally {
        // Clean up
        @unlink($viewPath);
    }
});
