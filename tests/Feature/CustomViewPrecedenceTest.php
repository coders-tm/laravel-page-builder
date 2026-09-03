<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use PageBuilder\Facades\Page;

test('custom view takes precedence over editor mode', function () {
    // 1. Create a physical Blade view in the workbench
    $viewPath = __DIR__.'/../../workbench/resources/views/pages/about-static.blade.php';
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
