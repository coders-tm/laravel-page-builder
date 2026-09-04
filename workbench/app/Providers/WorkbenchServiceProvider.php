<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use PageBuilder\Facades\Theme;
use PageBuilder\PageBuilder;
use Workbench\App\Models\Page;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Theme::set('default');

        config([
            'pagebuilder.languages' => [
                ['code' => 'en', 'name' => 'English'],
                ['code' => 'fr', 'name' => 'Français'],
                ['code' => 'es', 'name' => 'Español'],
            ],
        ]);

        PageBuilder::usePageModel(Page::class);
    }
}
