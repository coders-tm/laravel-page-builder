<?php

declare(strict_types=1);

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
