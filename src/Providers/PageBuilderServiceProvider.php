<?php

namespace Coderstm\PageBuilder\Providers;

use Coderstm\PageBuilder\Commands;
use Coderstm\PageBuilder\Facades;
use Coderstm\PageBuilder\Http\Middleware;
use Coderstm\PageBuilder\Registry;
use Coderstm\PageBuilder\Rendering;
use Coderstm\PageBuilder\Services;
use Coderstm\PageBuilder\Services\PageRegistry;
use Coderstm\PageBuilder\Services\PageRenderer;
use Coderstm\PageBuilder\Services\PageStorage;
use Coderstm\PageBuilder\Services\TemplateStorage;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Coderstm\PageBuilder\Support;
use Coderstm\PageBuilder\Support\TemplateVariableResolver;
use Coderstm\PageBuilder\Support\WrapperParser;
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
            return $app->make(Services\PageService::class);
        });

        $this->app->singleton('theme', function ($app) {
            return new Services\Theme;
        });

        $this->app->singleton(Support\Mix::class);

        // ─── Schema extraction ───────────────────────────────────

        $this->app->singleton(Registry\SchemaExtractor::class);

        // ─── Registries ──────────────────────────────────────────

        $this->app->singleton(Registry\SectionRegistry::class, function ($app) {
            return new Registry\SectionRegistry($app->make(Registry\SchemaExtractor::class));
        });

        $this->app->singleton(Registry\BlockRegistry::class, function ($app) {
            return new Registry\BlockRegistry($app->make(Registry\SchemaExtractor::class));
        });

        $this->app->singleton(Registry\LayoutParser::class, function ($app) {
            return new Registry\LayoutParser($app->make(Registry\SectionRegistry::class));
        });

        // ─── Page services ───────────────────────────────────────

        $this->app->singleton(Services\PageCache::class);
        $this->app->singleton(PageRegistry::class);
        $this->app->singleton(PageStorage::class);
        $this->app->singleton(TemplateStorage::class);
        $this->app->singleton(ThemeSettings::class);

        // ─── Support utilities ───────────────────────────────────

        $this->app->singleton(WrapperParser::class);
        $this->app->singleton(TemplateVariableResolver::class);

        // ─── Rendering ──────────────────────────────────────────

        $this->app->singleton(Rendering\Renderer::class, function ($app) {
            return new Rendering\Renderer(
                $app->make(Registry\SectionRegistry::class),
                $app->make(Registry\BlockRegistry::class),
            );
        });

        $this->app->singleton(PageRenderer::class, function ($app) {
            return new PageRenderer(
                $app->make(Rendering\Renderer::class),
                $app->make(PageStorage::class),
                $app->make(WrapperParser::class),
                $app->make(Services\PageCache::class),
            );
        });
    }

    public function boot(): void
    {
        // Register section paths from config
        if ($sections = config('pagebuilder.sections')) {
            Facades\Section::add($sections);
        }

        // Register block paths from config
        if ($blocks = config('pagebuilder.blocks')) {
            Facades\Block::add($blocks);
        }

        // Set active theme
        if ($activeTheme = config('theme.active')) {
            Services\Theme::set($activeTheme);
        }

        // Register theme middleware
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(Middleware\RequestThemeMiddleware::class);
        Route::aliasMiddleware('theme', Middleware\ThemeMiddleware::class);

        // Public Page routes
        Route::middleware(['web'])->group(function () {
            Facades\Page::routes();
        });

        // Page builder routes
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
            Commands\InstallPageBuilder::class,
            Commands\RegeneratePages::class,
            Commands\ThemeLink::class,
        ]);

        // Share $theme globally with all Blade views
        View::share('theme', $this->app->make(ThemeSettings::class));

        // ─── Blade directives & precompiler ──────────────────────
        Rendering\BladeDirectives::register();
        Rendering\BladeDirectives::registerPrecompiler();
    }
}
