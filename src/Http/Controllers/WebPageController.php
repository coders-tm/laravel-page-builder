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

namespace PageBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PageBuilder\Facades\Page;
use PageBuilder\PageBuilder;

class WebPageController extends Controller
{
    public function pages(Request $request, string $slug): mixed
    {
        if ($request->has('editor') && PageBuilder::checkAuth($request)) {
            return Page::render($slug, $request->all(), true);
        }

        return Page::render($slug, $request->all());
    }
}
