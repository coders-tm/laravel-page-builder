<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Rendering;

use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Registry\LayoutParser;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Coderstm\PageBuilder\Support\PageData;
use Illuminate\Support\Facades\Blade;

/**
 * Registers Blade directives for the page builder.
 *
 *   @blocks($section|$block)  — renders blocks within a section or container block
 *
 *   @schema([...])            — no-op (extracted at registration time)
 *
 *   @pbEditorClass(...)       — renders the <html> class attribute
 */
class BladeDirectives
{
    /**
     * Register all page builder Blade directives.
     */
    public static function register(): void
    {
        // @block($block, $parent) — renders a single block
        Blade::directive('block', function (string $expression) {
            return <<<PHP
<?php
echo app(\Coderstm\PageBuilder\Rendering\Renderer::class)->renderBlock({$expression});
?>
PHP;
        });

        // @blocks($section) — renders all blocks within a section
        // @blocks($block)   — renders child blocks inside a container block
        Blade::directive('blocks', function (string $expression) {
            return <<<PHP
<?php
\$__pb_ctx = {$expression};
if (\$__pb_ctx instanceof \Coderstm\PageBuilder\Components\Block) {
    echo app(\Coderstm\PageBuilder\Rendering\Renderer::class)
        ->renderBlockChildren(\$__pb_ctx);
} else {
    echo app(\Coderstm\PageBuilder\Rendering\Renderer::class)
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
        Blade::directive('sections', function (string $expression) {
            return sprintf(
                '<?php echo \Coderstm\PageBuilder\Rendering\BladeDirectives::renderLayoutSection($__pb_layout ?? null, %s); ?>',
                trim($expression),
            );
        });

        // @pbEditorClass('dark') — renders class="dark js pb-design-mode" in editor mode.
        Blade::directive('pbEditorClass', function (string $expression) {
            $expression = trim($expression);

            return $expression === ''
                ? "<?php echo \Coderstm\PageBuilder\PageBuilder::classAttribute(); ?>"
                : "<?php echo \Coderstm\PageBuilder\PageBuilder::classAttribute({$expression}); ?>";
        });

        // @themeFont — emits Google Fonts <link> tags for any google_font settings
        Blade::directive('themeFont', function () {
            return '<?php echo \Coderstm\PageBuilder\Rendering\BladeDirectives::renderThemeFont(); ?>';
        });
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
    public static function renderThemeFont(): string
    {
        $themeSettings = app(ThemeSettings::class);
        $values = $themeSettings->values();
        $fonts = [];

        foreach ($themeSettings->schema() as $group) {
            foreach ($group['settings'] ?? [] as $setting) {
                if (($setting['type'] ?? '') !== 'google_font') {
                    continue;
                }

                $family = $values[$setting['id']] ?? ($setting['default'] ?? null);

                if ($family) {
                    $fonts[] = $family;
                }
            }
        }

        $fonts = array_unique($fonts);

        if (empty($fonts)) {
            return '';
        }

        $query = implode('&', array_map(
            static fn (string $f) => 'family='.rawurlencode($f).':wght@400;500;600;700',
            $fonts
        ));

        $href = 'https://fonts.googleapis.com/css2?'.$query.'&display=swap';

        return implode("\n", [
            '<link rel="preconnect" href="https://fonts.googleapis.com">',
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
            '<link href="'.e($href).'" rel="stylesheet">',
        ]);
    }
}
