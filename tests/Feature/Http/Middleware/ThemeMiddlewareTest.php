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

use Illuminate\Support\Facades\File;
use PageBuilder\Facades\Theme;
use PageBuilder\Http\Middleware\RequestThemeMiddleware;

beforeEach(function () {
    File::makeDirectory(base_path('themes/test'), 0755, true);
    File::makeDirectory(base_path('themes/foundation'), 0755, true);

    $this->app['router']->middleware([RequestThemeMiddleware::class])->get('/foo', function () {
        return Theme::active();
    });

    $this->app['router']->get('/bar', function () {
        return Theme::active();
    })->middleware('theme:foundation');
});

afterEach(function () {
    File::deleteDirectory(base_path('themes/test'));
    File::deleteDirectory(base_path('themes/foundation'));
});

test('active theme is applied when user requests with theme parameter', function () {
    $this->get('/foo?theme=test')->assertStatus(200)->assertSee('test');
});

test('active theme is applied when middleware is used', function () {
    $this->get('/bar')->assertStatus(200)->assertSee('foundation');
});
