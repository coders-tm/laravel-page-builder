<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature;

use PageBuilder\Facades\Page;
use PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageEditorFrameTest extends TestCase
{
    use RefreshDatabase;

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
