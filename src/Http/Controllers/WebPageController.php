<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Http\Controllers;

use Coderstm\PageBuilder\Facades\Page;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebPageController extends Controller
{
    public function pages(Request $request, string $slug): mixed
    {
        return Page::render($slug, $request->all(), $request->has('editor'));
    }
}
