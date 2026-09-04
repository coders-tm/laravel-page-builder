<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use PageBuilder\Http\Controllers\WebPageController;
use PageBuilder\Http\Middleware\RequestThemeMiddleware;

beforeEach(function () {
    $this->app['router']->middleware([RequestThemeMiddleware::class])
        ->get('/shop', [WebPageController::class, 'pages'])
        ->defaults('slug', 'shop');

    foreach (['alpha', 'beta'] as $theme) {
        $pagesDir = base_path("themes/{$theme}/views/pages");
        File::ensureDirectoryExists($pagesDir);

        File::put("{$pagesDir}/shop.json", json_encode([
            'sections' => [
                'hero-1' => [
                    'type' => 'hero',
                    'settings' => ['title' => strtoupper($theme).' Shop Content'],
                ],
            ],
            'order' => ['hero-1'],
        ]));
    }
});

afterEach(function () {
    File::deleteDirectory(base_path('themes/alpha'));
    File::deleteDirectory(base_path('themes/beta'));
});

test('theme alpha returns alpha page content', function () {
    $this->get('/shop?theme=alpha')
        ->assertOk()
        ->assertSee('ALPHA Shop Content');
});

test('theme beta returns beta page content', function () {
    $this->get('/shop?theme=beta')
        ->assertOk()
        ->assertSee('BETA Shop Content');
});

test('each theme returns its own content not the others', function () {
    $this->get('/shop?theme=alpha')
        ->assertOk()
        ->assertSee('ALPHA Shop Content')
        ->assertDontSee('BETA Shop Content');

    $this->get('/shop?theme=beta')
        ->assertOk()
        ->assertSee('BETA Shop Content')
        ->assertDontSee('ALPHA Shop Content');
});

test('second request for same theme reflects disk updates', function () {
    $this->get('/shop?theme=alpha')->assertSee('ALPHA Shop Content');

    File::put(base_path('themes/alpha/views/pages/shop.json'), json_encode([
        'sections' => [
            'hero-1' => ['type' => 'hero', 'settings' => ['title' => 'Alpha Updated On Disk']],
        ],
        'order' => ['hero-1'],
    ]));

    $this->get('/shop?theme=alpha')
        ->assertOk()
        ->assertSee('Alpha Updated On Disk')
        ->assertDontSee('ALPHA Shop Content');
});
