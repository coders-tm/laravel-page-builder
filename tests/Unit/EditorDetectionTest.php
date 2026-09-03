<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use PageBuilder\PageBuilder;

test('editor detected via query param true', function () {
    $request = Request::create('/test', 'GET', ['pb-editor' => 'true']);
    $this->app->instance('request', $request);

    expect(PageBuilder::editor())->toBeTrue();
});
test('editor detected via query param 1', function () {
    $request = Request::create('/test', 'GET', ['pb-editor' => '1']);
    $this->app->instance('request', $request);

    expect(PageBuilder::editor())->toBeTrue();
});
test('editor not detected via query param false', function () {
    $request = Request::create('/test', 'GET', ['pb-editor' => 'false']);
    $this->app->instance('request', $request);

    expect(PageBuilder::editor())->toBeFalse();
});
test('editor not detected when param missing', function () {
    $request = Request::create('/test', 'GET');
    $this->app->instance('request', $request);

    expect(PageBuilder::editor())->toBeFalse();
});
