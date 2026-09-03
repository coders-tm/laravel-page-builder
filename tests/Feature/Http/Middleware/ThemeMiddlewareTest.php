<?php

declare(strict_types=1);

use PageBuilder\Facades\Theme;
use PageBuilder\Http\Middleware\RequestThemeMiddleware;

beforeEach(function () {
    $this->app['router']->middleware([RequestThemeMiddleware::class])->get('/foo', function () {
        return Theme::active();
    });

    $this->app['router']->get('/bar', function () {
        return Theme::active();
    })->middleware('theme:foundation');
});

test('active theme is applied when user requests with theme parameter', function () {
    $this->get('/foo?theme=test')->assertStatus(200)->assertSee('test');
});

test('active theme is applied when middleware is used', function () {
    $this->get('/bar')->assertStatus(200)->assertSee('foundation');
});
