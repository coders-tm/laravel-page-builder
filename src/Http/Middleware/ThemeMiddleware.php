<?php

namespace PageBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PageBuilder\Services\Theme;

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
