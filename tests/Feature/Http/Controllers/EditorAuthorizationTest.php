<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Http\Controllers;

use PageBuilder\Facades\Page;
use PageBuilder\PageBuilder;
use PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Workbench\App\Models\Page as ModelsPage;

class EditorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ModelsPage::create([
            'slug' => 'test-page',
            'title' => 'Test Page',
            'is_active' => true,
        ]);

        Page::routes();
    }

    protected function tearDown(): void
    {
        PageBuilder::$authCallback = null;
        parent::tearDown();
    }

    public function test_editor_frame_renders_when_authorized_by_default(): void
    {
        $response = $this->get('/test-page?editor=true');

        $response->assertOk();
        $response->assertViewIs('pagebuilder::layout');
    }

    public function test_editor_frame_is_skipped_when_unauthorized(): void
    {
        // Mock unauthorized
        PageBuilder::auth(fn () => false);

        $response = $this->get('/test-page?editor=true');

        $response->assertOk();
        // Should render the regular page view, not the editor frame (layout)
        $response->assertViewIs('pagebuilder::page');
    }

    public function test_editor_frame_renders_when_explicitly_authorized(): void
    {
        // Mock authorized
        PageBuilder::auth(fn () => true);

        $response = $this->get('/test-page?editor=true');

        $response->assertOk();
        $response->assertViewIs('pagebuilder::layout');
    }

    public function test_editor_frame_receives_request_context_in_callback(): void
    {
        $checkedRequest = null;
        PageBuilder::auth(function ($request) use (&$checkedRequest) {
            $checkedRequest = $request;

            return true;
        });

        $this->get('/test-page?editor=true&foo=bar');

        $this->assertInstanceOf(Request::class, $checkedRequest);
        $this->assertEquals('bar', $checkedRequest->query('foo'));
    }
}
