<?php

use Coderstm\PageBuilder\Http\Controllers\AssetController;
use Coderstm\PageBuilder\Http\Controllers\PageBuilderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Page Builder Routes
|--------------------------------------------------------------------------
|
| These routes handle the page builder API endpoints.
| Used by the React editor for CRUD operations and section rendering.
| Preview is handled via the real page URLs with ?pb-editor=1 query param.
|
*/

$basePath = config('pagebuilder.basePath', 'pagebuilder');

Route::prefix($basePath)->as('pagebuilder.')->group(function () {
    Route::get('{slug}.json', [PageBuilderController::class, 'page'])->where('slug', '[^.]+')->defaults('slug', 'home')->name('page');
    Route::post('{slug}', [PageBuilderController::class, 'savePage'])->where('slug', '.*')->name('save-page');
    Route::post('render-section', [PageBuilderController::class, 'renderSection'])->name('render-section');
    Route::post('render-block', [PageBuilderController::class, 'renderBlock'])->name('render-block');

    // Theme settings
    Route::get('theme-settings', [PageBuilderController::class, 'themeSettings'])->name('theme-settings');
    Route::post('theme-settings', [PageBuilderController::class, 'saveThemeSettings'])->name('theme-settings.save');

    // Asset management
    Route::get('assets', [AssetController::class, 'index'])->name('assets');
    Route::post('assets/upload', [AssetController::class, 'upload'])->name('assets.upload');
});

// Redirect to home page builder editor if accessing /{basePath} without slug
Route::redirect($basePath, $basePath.'/home', 301)->name('pagebuilder.index');

// Editor routes (Blade layout)
Route::get($basePath.'/{slug?}', [PageBuilderController::class, 'editor'])->where('slug', '.*')->name('pagebuilder.editor');
