<?php

declare(strict_types=1);

namespace PageBuilder\Http\Controllers;

use PageBuilder\Facades\Page;
use PageBuilder\PageBuilder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
