<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\Route;
use PageBuilder\Facades\Page;
use PageBuilder\Services\PageRegistry;

test('pages routes are registered from registry', function () {
    // 1. Put some pages into the registry
    app(PageRegistry::class)->put([
        'test-page' => ['title' => 'Test Page', 'slug' => 'test-page'],
    ]);

    // 2. Trigger route registration
    Page::routes();

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->hasNamedRoute('pages.test-page'))->toBeTrue();
    expect(route('pages.test-page'))->toBe(url('/test-page'));
});
