<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use PageBuilder\Facades\Page;
use PageBuilder\PageBuilder;
use Workbench\App\Models\Page as ModelsPage;

beforeEach(function () {
    ModelsPage::create([
        'slug' => 'test-page',
        'title' => 'Test Page',
        'is_active' => true,
    ]);

    Page::routes();
});
afterEach(function () {
    PageBuilder::$authCallback = null;
});
test('editor frame renders when authorized by default', function () {
    $response = $this->get('/test-page?editor=true');

    $response->assertOk();
    $response->assertViewIs('pagebuilder::layout');
});
test('editor frame is skipped when unauthorized', function () {
    // Mock unauthorized
    PageBuilder::auth(fn () => false);

    $response = $this->get('/test-page?editor=true');

    $response->assertOk();

    // Should render the regular page view, not the editor frame (layout)
    $response->assertViewIs('pagebuilder::page');
});
test('editor frame renders when explicitly authorized', function () {
    // Mock authorized
    PageBuilder::auth(fn () => true);

    $response = $this->get('/test-page?editor=true');

    $response->assertOk();
    $response->assertViewIs('pagebuilder::layout');
});
test('editor frame receives request context in callback', function () {
    $checkedRequest = null;
    PageBuilder::auth(function ($request) use (&$checkedRequest) {
        $checkedRequest = $request;

        return true;
    });

    $this->get('/test-page?editor=true&foo=bar');

    expect($checkedRequest)->toBeInstanceOf(Request::class);
    expect($checkedRequest->query('foo'))->toEqual('bar');
});
