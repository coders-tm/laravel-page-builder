<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Rendering;

use Illuminate\Support\Facades\Blade;
use PageBuilder\Contracts\RendererInterface;
use PageBuilder\PageBuilder;
use PageBuilder\Registry\LayoutParser;
use PageBuilder\Services\ThemeSettings;
use PageBuilder\Support\PageData;

/**
 * Registers Blade directives for the page builder.
 */
class BladeDirectives
{
    private const REQUEST_KEY = '_pb_layout_overrides';

    /**
     * Register all page builder Blade directives.
     */
    public static function register(): void
    {
        self::registerBlockDirectives();
        self::registerLayoutDirectives();
        self::registerUtilityDirectives();
    }

    private static function registerBlockDirectives(): void
    {
        Blade::directive('block', function (string $expression) {
            return <<<PHP
<?php
\$_pb_renderer = \$_pb_renderer ?? app(\PageBuilder\Contracts\RendererInterface::class);
echo \$_pb_renderer->renderBlock({$expression});
?>
PHP;
        });

        Blade::directive('blocks', function (string $expression) {
            return <<<PHP
<?php
\$_pb_renderer = \$_pb_renderer ?? app(\PageBuilder\Contracts\RendererInterface::class);
\$__pb_ctx = {$expression};
if (\$__pb_ctx instanceof \PageBuilder\Components\Block) {
    echo \$_pb_renderer->renderBlockChildren(\$__pb_ctx);
} else {
    echo \$_pb_renderer->renderBlocks(\$__pb_ctx);
}
unset(\$__pb_ctx);
?>
PHP;
        });
    }

    private static function registerLayoutDirectives(): void
    {
        Blade::directive('schema', function () {
            return '<?php /* @schema */ ?>';
        });

        Blade::directive('sections', function (string $expression) {
            return <<<PHP
<?php
\$__pb_overrides = \PageBuilder\Rendering\BladeDirectives::getPendingOverrides();
if (! empty(\$__pb_overrides) && isset(\$__pb_layout) && \$__pb_layout instanceof \PageBuilder\Support\PageData) {
    \$__pb_layout = \$__pb_layout->withMergedLayout(\$__pb_overrides);
}
echo \PageBuilder\Rendering\BladeDirectives::renderLayoutSection(\$__pb_layout ?? null, {$expression});
?>
PHP;
        });

        Blade::directive('layout', function (string $expression) {
            return sprintf(
                '<?php \PageBuilder\Rendering\BladeDirectives::storePendingOverrides(%s); ?>',
                $expression,
            );
        });
    }

    private static function registerUtilityDirectives(): void
    {
        Blade::directive('editor', function (string $expression) {
            $expr = trim($expression);

            return $expr === ''
                ? '<?php echo \\PageBuilder\\PageBuilder::classAttribute(); ?>'
                : "<?php echo \\PageBuilder\\PageBuilder::classAttribute({$expr}); ?>";
        });

        Blade::directive('fonts', function () {
            return '<?php echo \PageBuilder\Rendering\BladeDirectives::renderFonts(); ?>';
        });
    }

    /**
     * Store pending layout overrides for application by the @sections directive.
     *
     * Uses request attributes to avoid static state issues in Octane.
     *
     * @param  array<string, mixed>  $overrides  Partial layout config to merge
     */
    public static function storePendingOverrides(array $overrides): void
    {
        request()->attributes->set(self::REQUEST_KEY, $overrides);
    }

    /**
     * Retrieve and clear pending layout overrides.
     *
     * @return array<string, mixed>
     */
    public static function getPendingOverrides(): array
    {
        $overrides = request()->attributes->get(self::REQUEST_KEY, []);

        request()->attributes->remove(self::REQUEST_KEY);

        return $overrides;
    }

    /**
     * Render a layout section by key from the given PageData.
     *
     * Called at runtime by the compiled @sections() directive.
     * Searches header zone then footer zone — the directive does not need
     * to know which zone a key belongs to.
     *
     * @param  string  $key  Layout section key (e.g. "header", "footer")
     */
    public static function renderLayoutSection(
        ?PageData $layout,
        string $key,
    ): string {
        if ($layout === null) {
            // No $__pb_layout was injected — e.g. a custom route called view() directly
            // without going through PageService. Build the default 'page' layout so that
            // @sections('header') / @sections('footer') still render with schema defaults.
            $defaultData = app(LayoutParser::class)->defaultLayout('page');
            $layout = PageData::fromArray([], $defaultData);
        }

        $raw = $layout->layoutSection($key);

        if ($raw === null) {
            return '';
        }

        // Reuse existing Renderer::renderRawSection() — same path as body sections
        $renderer = app(RendererInterface::class);

        return $renderer->renderRawSection($key, $raw, PageBuilder::editor(), [
            '__pb_layout' => $layout,
            '__pb_page' => $layout,
        ]);
    }

    /**
     * Build Google Fonts <link> tags for every google_font setting in the theme schema.
     *
     * Uses the saved value when available, falls back to the setting default.
     * Returns an empty string when no google_font settings are configured.
     */
    public static function renderFonts(): string
    {
        return app(ThemeSettings::class)->fontElements();
    }
}
