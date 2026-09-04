<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use PageBuilder\Commands;
use PageBuilder\Contracts;
use PageBuilder\Contracts\SettingsStoreInterface;
use PageBuilder\Facades;
use PageBuilder\Http\Middleware;
use PageBuilder\PageBuilder;
use PageBuilder\Registry;
use PageBuilder\Rendering;
use PageBuilder\Services;
use PageBuilder\Services\LayoutSettings;
use PageBuilder\Services\PageRegistry;
use PageBuilder\Services\PageRenderer;
use PageBuilder\Services\PageStorage;
use PageBuilder\Services\TemplateStorage;
use PageBuilder\Services\ThemeSettings;
use PageBuilder\Support;
use PageBuilder\Support\TemplateVariableResolver;
use PageBuilder\Support\WrapperParser;

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

        $this->app->singleton(
            SettingsStoreInterface::class,
            Services\SettingsStore::class
        );

        $this->app->singleton(PageRegistry::class);
        $this->app->singleton(PageStorage::class);
        $this->app->singleton(TemplateStorage::class);

        $this->app->singleton(ThemeSettings::class, function ($app) {
            return new ThemeSettings($app->make(SettingsStoreInterface::class));
        });

        $this->app->singleton(LayoutSettings::class, function ($app) {
            return new LayoutSettings($app->make(SettingsStoreInterface::class));
        });

        // ─── Support utilities ───────────────────────────────────

        $this->app->singleton(WrapperParser::class);
        $this->app->singleton(TemplateVariableResolver::class);

        // ─── Rendering ──────────────────────────────────────────

        $this->app->singleton(Rendering\BlockHydrator::class, function ($app) {
            return new Rendering\BlockHydrator(
                $app->make(Registry\BlockRegistry::class),
            );
        });

        $this->app->singleton(Rendering\Renderer::class, function ($app) {
            return new Rendering\Renderer(
                $app->make(Registry\SectionRegistry::class),
                $app->make(Rendering\BlockHydrator::class),
            );
        });

        $this->app->alias(Rendering\Renderer::class, Contracts\RendererInterface::class);

        $this->app->singleton(PageRenderer::class, function ($app) {
            return new PageRenderer(
                $app->make(Rendering\Renderer::class),
                $app->make(PageStorage::class),
                $app->make(WrapperParser::class),
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
        Route::aliasMiddleware('theme', Middleware\ThemeMiddleware::class);

        if (! PageBuilder::$withoutRoutes) {
            PageBuilder::pageRoutes();
            PageBuilder::builderRoutes(config('pagebuilder.middleware', ['web']));
        }

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
            __DIR__.'/../../public' => public_path('statics'),
        ], 'pagebuilder-statics');

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

        // ─── Blade directives ────────────────────────────────────
        Rendering\BladeDirectives::register();
    }
}
