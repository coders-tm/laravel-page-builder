<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageEditorFrameTest extends TestCase
{
    public function test_renders_editor_frame_when_editor_param_present(): void
    {
        $request = Request::create('/home', 'GET', ['editor' => 'true']);
        $this->app->instance('request', $request);

        $response = Page::render('home', [], true);

        $this->assertInstanceOf(View::class, $response);
        $this->assertEquals('pagebuilder::layout', $response->name());
        $this->assertEquals('/', $response->getData()['config']['basePath']);
    }
}
