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

// API routes (JSON responses)
Route::prefix('pagebuilder')->group(function () {
    Route::get('pages', [PageBuilderController::class, 'pages']);
    Route::get('page/{slug?}', [PageBuilderController::class, 'page'])->defaults('slug', 'home');
    Route::post('render-section', [PageBuilderController::class, 'renderSection']);
    Route::post('render-block', [PageBuilderController::class, 'renderBlock']);
    Route::post('save-page', [PageBuilderController::class, 'savePage']);

    // Theme settings
    Route::get('theme-settings', [PageBuilderController::class, 'themeSettings']);
    Route::post('theme-settings', [PageBuilderController::class, 'saveThemeSettings']);

    // Asset management
    Route::get('assets', [AssetController::class, 'index']);
    Route::post('assets/upload', [AssetController::class, 'upload']);
});

// Redirect to home page builder editor if accessing /pagebuilder without slug
Route::redirect('pagebuilder', 'pagebuilder/home', 301);

// Editor routes (Blade layout)
Route::get('pagebuilder/{slug?}', [PageBuilderController::class, 'editor'])->name('pagebuilder.editor');
