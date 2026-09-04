<?php

declare(strict_types=1);

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
use Illuminate\Http\Response;
use PageBuilder\PageBuilder;

final class SetLangMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Sets the page builder language for multilanguage page resolution.
     * The language can be provided as a route parameter or read from the request.
     */
    public function handle(Request $request, Closure $next, ?string $lang = null): Response
    {
        $resolvedLang = $lang ?? $request->input('lang');

        PageBuilder::setLang($resolvedLang);

        return $next($request);
    }
}
