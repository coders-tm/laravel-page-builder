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

use PageBuilder\Facades\Page;
use PageBuilder\Http\Middleware\SetLangMiddleware;
use PageBuilder\PageBuilder;

beforeEach(function () {
    $this->app['router']->middleware([SetLangMiddleware::class])->get('/lang-test', function () {
        return PageBuilder::getLang() ?? 'null';
    });

    $this->app['router']->middleware(['lang:fr'])->get('/lang-route-param', function () {
        return PageBuilder::getLang() ?? 'null';
    });
});

afterEach(function () {
    PageBuilder::setLang(null);
});

test('middleware sets lang from route parameter', function () {
    $this->get('/lang-route-param')
        ->assertStatus(200)
        ->assertSee('fr');
});

test('middleware sets lang from request input', function () {
    $this->get('/lang-test?lang=de')
        ->assertStatus(200)
        ->assertSee('de');
});

test('middleware defaults to null when no lang provided', function () {
    $this->get('/lang-test')
        ->assertStatus(200)
        ->assertSee('null');
});

test('middleware resets lang when null parameter provided', function () {
    PageBuilder::setLang('fr');

    $this->app['router']->middleware(['lang:null'])->get('/lang-reset', function () {
        return PageBuilder::getLang() ?? 'null';
    });

    $this->get('/lang-reset')
        ->assertStatus(200)
        ->assertSee('null');
});

test('middleware named alias works', function () {
    $this->app['router']->get('/lang-alias', function () {
        return PageBuilder::getLang() ?? 'null';
    })->middleware('lang:es');

    $this->get('/lang-alias')
        ->assertStatus(200)
        ->assertSee('es');
});
