<?php

namespace PageBuilder\Http\Middleware;

use Closure;
use PageBuilder\Services\Theme;
use Illuminate\Http\Request;

class ThemeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $theme)
    {
        Theme::set($theme);

        return $next($request);
    }
}
