<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
