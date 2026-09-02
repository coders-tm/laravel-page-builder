<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit;

use PageBuilder\PageBuilder;
use PageBuilder\Tests\TestCase;
use Illuminate\Http\Request;

class EditorDetectionTest extends TestCase
{
    public function test_editor_detected_via_query_param_true(): void
    {
        $request = Request::create('/test', 'GET', ['pb-editor' => 'true']);
        $this->app->instance('request', $request);

        $this->assertTrue(PageBuilder::editor());
    }

    public function test_editor_detected_via_query_param_1(): void
    {
        $request = Request::create('/test', 'GET', ['pb-editor' => '1']);
        $this->app->instance('request', $request);

        $this->assertTrue(PageBuilder::editor());
    }

    public function test_editor_not_detected_via_query_param_false(): void
    {
        $request = Request::create('/test', 'GET', ['pb-editor' => 'false']);
        $this->app->instance('request', $request);

        $this->assertFalse(PageBuilder::editor());
    }

    public function test_editor_not_detected_when_param_missing(): void
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $this->assertFalse(PageBuilder::editor());
    }
}
