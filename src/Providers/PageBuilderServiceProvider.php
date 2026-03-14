<?php

namespace Coderstm\PageBuilder\Providers;

use Coderstm\PageBuilder\Commands\RegeneratePages;
use Coderstm\PageBuilder\Commands\ThemeLink;
use Coderstm\PageBuilder\Facades\Block;
use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Facades\Section;
use Coderstm\PageBuilder\Http\Middleware\RequestThemeMiddleware;
use Coderstm\PageBuilder\Http\Middleware\ThemeMiddleware;
use Coderstm\PageBuilder\Registry\BlockRegistry;
use Coderstm\PageBuilder\Registry\LayoutParser;
use Coderstm\PageBuilder\Registry\SchemaExtractor;
use Coderstm\PageBuilder\Registry\SectionRegistry;
use Coderstm\PageBuilder\Rendering\BladeDirectives;
use Coderstm\PageBuilder\Rendering\Renderer;
use Coderstm\PageBuilder\Services\PageRegistry;
use Coderstm\PageBuilder\Services\PageRenderer;
use Coderstm\PageBuilder\Services\PageService;
use Coderstm\PageBuilder\Services\PageStorage;
use Coderstm\PageBuilder\Services\Theme;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Coderstm\PageBuilder\Support\Mix;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PageBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register page builder services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/pagebuilder.php',
            'pagebuilder'
        );

        // ─── Core services ───────────────────────────────────────

        $this->app->singleton('page-service', function ($app) {
            return $app->make(PageService::class);
        });

        $this->app->singleton('theme', function ($app) {
            return new Theme;
        });

        $this->app->singleton(Mix::class);

        // ─── Schema extraction ───────────────────────────────────

        $this->app->singleton(SchemaExtractor::class);

        // ─── Registries ──────────────────────────────────────────

        $this->app->singleton(SectionRegistry::class, function ($app) {
            return new SectionRegistry($app->make(SchemaExtractor::class));
        });

        $this->app->singleton(BlockRegistry::class, function ($app) {
            return new BlockRegistry($app->make(SchemaExtractor::class));
        });

        $this->app->singleton(LayoutParser::class, function ($app) {
            return new LayoutParser($app->make(SectionRegistry::class));
        });

        // ─── Page services ───────────────────────────────────────

        $this->app->singleton(PageRegistry::class);
        $this->app->singleton(PageStorage::class);
        $this->app->singleton(ThemeSettings::class);

        // ─── Rendering ──────────────────────────────────────────

        $this->app->singleton(Renderer::class, function ($app) {
            return new Renderer(
                $app->make(SectionRegistry::class),
                $app->make(BlockRegistry::class),
            );
        });

        $this->app->singleton(PageRenderer::class, function ($app) {
            return new PageRenderer(
                $app->make(Renderer::class),
                $app->make(PageStorage::class),
            );
        });
    }

    public function boot(): void
    {
        // Register section paths from config
        if ($sections = config('pagebuilder.sections')) {
            Section::add($sections);
        }

        // Register block paths from config
        if ($blocks = config('pagebuilder.blocks')) {
            Block::add($blocks);
        }

        // Register page routes
        Page::routes();

        // Set active theme
        if ($activeTheme = config('theme.active')) {
            Theme::set($activeTheme);
        }

        // Register theme middleware
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(RequestThemeMiddleware::class);
        Route::aliasMiddleware('theme', ThemeMiddleware::class);

        // Routes
        Route::middleware(config('pagebuilder.middleware', ['web']))
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
            });

        // Views & migrations
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'pagebuilder');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Publishable resources
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/pagebuilder'),
        ], 'pagebuilder-views');

        $this->publishes([
            __DIR__.'/../../config/pagebuilder.php' => config_path('pagebuilder.php'),
        ], 'pagebuilder-config');

        $this->publishes([
            __DIR__.'/../../dist' => public_path('pagebuilder'),
        ], 'pagebuilder-assets');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'pagebuilder-migrations');

        // Commands
        $this->commands([
            RegeneratePages::class,
            ThemeLink::class,
        ]);

        // Share $theme globally with all Blade views
        View::share('theme', $this->app->make(ThemeSettings::class));

        // ─── Blade directives & precompiler ──────────────────────
        BladeDirectives::register();
        BladeDirectives::registerPrecompiler();
    }
}
