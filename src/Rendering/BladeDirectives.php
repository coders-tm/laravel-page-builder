<?php declare(strict_types=1);

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
use PageBuilder\PageBuilder;
use PageBuilder\Registry\LayoutParser;
use PageBuilder\Services\ThemeSettings;
use PageBuilder\Support\PageData;

/**
 * Registers Blade directives for the page builder.
 *
 *   @blocks($section|$block)  — renders blocks within a section or container block
 *
 *   @schema([...])            — no-op (extracted at registration time)
 *
 *   @layout([...])            — partial layout config override (header/footer sections)
 *
 *   @editor(...)       — renders the <html> class attribute
 *
 *   @fonts                  — emits Google Fonts <link> tags
 */
class BladeDirectives
{
    /**
     * Pending layout overrides stored by @layout directive, awaiting application
     * by the @sections directive when it renders a layout section.
     *
     * @var array<string, mixed>
     */
    private static array $pendingLayoutOverrides = [];

    /**
     * Register all page builder Blade directives.
     */
    public static function register(): void
    {
        // @block($block, $parent) — renders a single block
        Blade::directive('block', function (string $expression) {
            return <<<PHP
<?php
echo app(\PageBuilder\Rendering\Renderer::class)->renderBlock({$expression});
?>
PHP;
        });

        // @blocks($section) — renders all blocks within a section
        // @blocks($block)   — renders child blocks inside a container block
        Blade::directive('blocks', function (string $expression) {
            return <<<PHP
<?php
\$__pb_ctx = {$expression};
if (\$__pb_ctx instanceof \PageBuilder\Components\Block) {
    echo app(\PageBuilder\Rendering\Renderer::class)
        ->renderBlockChildren(\$__pb_ctx);
} else {
    echo app(\PageBuilder\Rendering\Renderer::class)
        ->renderBlocks(\$__pb_ctx);
}
unset(\$__pb_ctx);
?>
PHP;
        });

        // @schema() — no-op; extracted at registration time, ignored at render
        Blade::directive('schema', function () {
            return '<?php /* @schema */ ?>';
        });

        // @sections('key')
        // Renders a layout section (header, footer, etc.) from $__pb_layout.
        // Searches header zone then footer zone by key — no second argument needed.
        // Also applies any pending layout overrides from @layout before rendering.
        Blade::directive('sections', function (string $expression) {
            return <<<PHP
<?php
\$__pb_overrides = \PageBuilder\Rendering\BladeDirectives::getPendingOverrides();
if (! empty(\$__pb_overrides) && isset(\$__pb_layout) && \$__pb_layout instanceof \PageBuilder\Support\PageData) {
    \$__pb_layout->mergeLayout(\$__pb_overrides);
}
echo \PageBuilder\Rendering\BladeDirectives::renderLayoutSection(\$__pb_layout ?? null, {$expression});
?>
PHP;
        });

        // @editor('dark') — renders class="dark js pb-design-mode" in editor mode.
        Blade::directive('editor', function (string $expression) {
            $expression = trim($expression);

            return $expression === ''
                ? "<?php echo \PageBuilder\PageBuilder::classAttribute(); ?>"
                : "<?php echo \PageBuilder\PageBuilder::classAttribute({$expression}); ?>";
        });

        // @fonts — emits Google Fonts <link> tags for any google_font settings
        Blade::directive('fonts', function () {
            return '<?php echo \PageBuilder\Rendering\BladeDirectives::renderFonts(); ?>';
        });

        // @layout(['header' => [...], 'footer' => [...]])
        // Stores a partial layout config that will be merged into $__pb_layout
        // by the next @sections() directive call. Allows custom Blade pages to
        // tweak header/footer sections without a full layout object.
        Blade::directive('layout', function (string $expression) {
            return sprintf(
                '<?php \PageBuilder\Rendering\BladeDirectives::storePendingOverrides(%s); ?>',
                $expression,
            );
        });
    }

    /**
     * Store pending layout overrides for application by the @sections directive.
     *
     * Called by the compiled @layout directive when a layout array is provided.
     *
     * @param  array<string, mixed>  $overrides  Partial layout config to merge
     */
    public static function storePendingOverrides(array $overrides): void
    {
        self::$pendingLayoutOverrides = $overrides;
    }

    /**
     * Retrieve and clear pending layout overrides.
     *
     * @return array<string, mixed>
     */
    public static function getPendingOverrides(): array
    {
        $overrides = self::$pendingLayoutOverrides;
        self::$pendingLayoutOverrides = [];

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
        $renderer = app(Renderer::class);

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
