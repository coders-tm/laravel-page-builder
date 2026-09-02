<?php

namespace PageBuilder\Tests\Feature\Http\Middleware;

use PageBuilder\Facades\Theme;
use PageBuilder\Http\Middleware\RequestThemeMiddleware;
use PageBuilder\Tests\TestCase;

class ThemeMiddlewareTest extends TestCase
{
    protected function defineRoutes($router)
    {
        $router->middleware([RequestThemeMiddleware::class])->get('/foo', function () {
            return Theme::active();
        });

        $router->get('/bar', function () {
            return Theme::active();
        })->middleware('theme:foundation');
    }

    public function test_active_theme_is_applied_when_user_requests_with_theme_parameter()
    {
        $this->get('/foo?theme=test')->assertStatus(200)->assertSee('test');
    }

    public function test_active_theme_is_applied_when_middleware_is_used()
    {
        $this->get('/bar')->assertStatus(200)->assertSee('foundation');
    }
}
