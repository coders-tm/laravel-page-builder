<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use PageBuilder\Facades\Page;
use PageBuilder\Observers\PageObserver;
use PageBuilder\Registry\BlockRegistry;
use PageBuilder\Registry\SectionRegistry;
use PageBuilder\Services\PageRegistry;
use PageBuilder\Services\TemplateStorage;
use PageBuilder\Services\ThemeSettings;
use RuntimeException;

class PageBuilder
{
    /**
     * Runtime editor mode override.
     * null = detect via ?pb-editor=1 query param | true/false = forced.
     */
    protected static ?bool $editorOverride = null;

    /**
     * The callback used to authorize the editor.
     *
     * @var Closure|null
     */
    public static $authCallback;

    /**
     * Set to true to prevent the service provider from auto-registering any routes.
     */
    public static bool $withoutRoutes = false;

    /**
     * The cache key for storing pages data.
     */
    public static string $pageCacheKey = 'pagebuilder.pages';

    /**
     * The page model class name.
     *
     * @var string
     */
    public static $pageModel = Models\Page::class;

    /**
     * Set to true to prevent the service provider from auto-registering any routes.
     */
    public static function withoutRoutes(bool $withoutRoutes = true): void
    {
        static::$withoutRoutes = $withoutRoutes;
    }

    /**
     * Register public page routes with the given middleware.
     */
    public static function pageRoutes(array $middleware = ['web']): void
    {
        Route::middleware($middleware)->group(function () {
            Page::routes();
        });
    }

    /**
     * Register page builder editor routes with the given middleware.
     */
    public static function builderRoutes(array $middleware = []): void
    {
        Route::middleware($middleware)->group(function () {
            require __DIR__.'/../routes/web.php';
        });
    }

    /**
     * Set the model to be used by the page builder and register observer.
     */
    public static function usePageModel(string $model): void
    {
        self::$pageModel = $model;

        $model::observe(PageObserver::class);
    }

    /**
     * Force editor mode on, regardless of query string. Mainly for testing purposes.
     */
    public static function enableEditor(): void
    {
        static::$editorOverride = true;
    }

    /** Restore query-string-based editor detection. */
    public static function disableEditor(): void
    {
        static::$editorOverride = null;
    }

    /**
     * Whether editor mode is active.
     *
     * Checks for a runtime override first, then falls back to the presence of ?pb-editor=1 in the query string.
     */
    public static function editor(): bool
    {
        if (static::$editorOverride !== null) {
            return static::$editorOverride;
        }

        return request()->boolean('pb-editor');
    }

    /**
     * Register the editor authorization callback.
     *
     * @return void
     */
    public static function auth(Closure $callback)
    {
        static::$authCallback = $callback;
    }

    /**
     * Check if the request is authorized to access the editor.
     *
     * @return bool
     */
    public static function checkAuth(Request $request)
    {
        return (static::$authCallback ?: function () {
            return true;
        })($request);
    }

    /**
     * Get the editor class for the <html> tag.
     */
    public static function class(): string
    {
        return static::editor() ? 'js pb-design-mode' : '';
    }

    /**
     * Get the full class attribute for the <html> tag.
     */
    public static function classAttribute(string ...$classes): string
    {
        $class = trim(implode(' ', array_filter([
            ...$classes,
            static::class(),
        ], static fn (string $class): bool => trim($class) !== '')));

        return $class === '' ? '' : 'class="'.e($class).'"';
    }

    /**
     * Get the CSS for the PageBuilder editor.
     *
     * @return HtmlString
     */
    public static function css()
    {
        if (file_exists(__DIR__.'/../dist/hot')) {
            return new HtmlString('');
        }

        if (($css = @file_get_contents(__DIR__.'/../dist/app.css')) === false) {
            throw new RuntimeException('Unable to load the PageBuilder editor CSS. Please run "npm run build" in the package root.');
        }

        return new HtmlString("<style>{$css}</style>");
    }

    /**
     * Get the JS for the PageBuilder editor.
     *
     * @return HtmlString
     */
    public static function js()
    {
        if (file_exists($hot = __DIR__.'/../dist/hot')) {
            $url = rtrim(file_get_contents($hot), '/');

            return new HtmlString(
                "<script type='module' src='{$url}/@vite/client'></script>\n".
                    "<script type='module'>\n".
                    "import RefreshRuntime from '{$url}/@react-refresh'\n".
                    "RefreshRuntime.injectIntoGlobalHook(window)\n".
                    "window.\$RefreshReg\$ = () => {}\n".
                    "window.\$RefreshSig\$ = () => (type) => type\n".
                    "window.__vite_plugin_react_preamble_installed__ = true\n".
                    "</script>\n".
                    "<script type='module' src='{$url}/resources/js/main.tsx'></script>"
            );
        }

        if (($js = @file_get_contents(__DIR__.'/../dist/app.umd.js')) === false) {
            throw new RuntimeException('Unable to load the PageBuilder editor JavaScript. Please run "npm run build" in the package root.');
        }

        return new HtmlString("<script type='text/javascript'>{$js}</script>");
    }

    /**
     * Get the default JavaScript variables for PageBuilder.
     *
     * @return array
     */
    public static function scriptVariables()
    {
        $pages = app(PageRegistry::class);
        $registry = app(SectionRegistry::class);
        $blocks = app(BlockRegistry::class);

        return [
            'baseUrl' => url(config('pagebuilder.prefix', 'pagebuilder')),
            'basePath' => config('pagebuilder.basePath', '/'),
            'pages' => array_merge(
                [
                    [
                        'slug' => 'home',
                        'title' => 'Home',
                    ],
                ],
                // Build a flat array of pages for the frontend where the slug includes its parent prefix when present (parent/slug).
                collect($pages->pages())
                    ->values()
                    ->map(static fn (array $page): array => [
                        ...$page,
                        'slug' => (! empty($page['parent'])) ? ($page['parent'].'/'.$page['slug']) : $page['slug'],
                    ])
                    ->all()
            ),
            'sections' => $registry->get(),
            'blocks' => $blocks->get(),
            'themeSettings' => app(ThemeSettings::class)->toArray(),
            'preservedParams' => config('pagebuilder.preserved_params', []),
        ];
    }

    /**
     * Determine if a slug is a preserved system page.
     */
    public static function isPreservedPage(?string $slug): bool
    {
        if (! $slug) {
            return false;
        }

        $preservedPages = config('pagebuilder.preserved_pages', [
            'home',
            'admin',
            'user',
            'api',
            'storage',
            'uploads',
            'files',
            'vendor',
        ]);

        return in_array(strtolower($slug), array_map('strtolower', $preservedPages));
    }

    /**
     * Get all available templates.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function templates(): array
    {
        return app(TemplateStorage::class)->all();
    }
}
