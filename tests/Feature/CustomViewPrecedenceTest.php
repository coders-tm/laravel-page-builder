<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CustomViewPrecedenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_view_takes_precedence_over_editor_mode(): void
    {
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
            $this->assertInstanceOf(\Illuminate\View\View::class, $response);
            $this->assertEquals('pages.about-static', $response->name());

            $html = $response->render();
            $this->assertStringContainsString('Custom Static Content', $html);

            // It should NOT contain editor attributes if it's the static view
            $this->assertStringNotContainsString('data-pb-section', $html);
        } finally {
            // Clean up
            @unlink($viewPath);
        }
    }
}
