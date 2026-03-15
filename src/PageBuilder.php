<?php

namespace Coderstm\PageBuilder;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Observers\PageObserver;
use Coderstm\PageBuilder\Registry\BlockRegistry;
use Coderstm\PageBuilder\Registry\SectionRegistry;
use Coderstm\PageBuilder\Services\PageRegistry;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Illuminate\Support\HtmlString;
use RuntimeException;

class PageBuilder
{
    /**
     * Runtime editor mode override.
     * null = detect via ?pb-editor=1 query param | true/false = forced.
     */
    protected static ?bool $editorOverride = null;

    /**
     * The page model class name.
     *
     * @var string
     */
    public static $pageModel = Models\Page::class;

    /**
     * Set the model to be used by the page builder and register observer.
     */
    public static function usePageModel(string $model): void
    {
        self::$pageModel = $model;

        $model::observe(PageObserver::class);
    }

    /** Force editor mode on (use when ?pb-editor=1 is not available, e.g. API previews). */
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
     * Returns the runtime override when set, otherwise checks ?pb-editor=1.
     */
    public static function editor(): bool
    {
        if (static::$editorOverride !== null) {
            return static::$editorOverride;
        }

        return request()->query('pb-editor') == '1';
    }

    /**
     * Get the editor class for the <html> tag.
     */
    public static function class(): string
    {
        return static::editor() ? 'js pb-design-mode' : '';
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
            'baseUrl' => config('app.url').'/pagebuilder',
            'appUrl' => config('app.url'),
            'pages' => array_merge([
                [
                    'slug' => 'home',
                    'title' => 'Home',
                ],
            ], array_values($pages->pages())),
            'sections' => $registry->get(),
            'blocks' => $blocks->get(),
            'themeSettings' => app(ThemeSettings::class)->toArray(),
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

        $preservedPages = config('pagebuilder.preserved_pages', ['home']);

        return in_array(strtolower($slug), array_map('strtolower', $preservedPages));
    }
}
