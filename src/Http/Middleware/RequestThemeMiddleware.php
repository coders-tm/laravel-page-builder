<?php

namespace PageBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PageBuilder\Services\Theme;

class RequestThemeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->filled('theme')) {
            Theme::set($request->theme);
        }

        return $next($request);
    }
}
