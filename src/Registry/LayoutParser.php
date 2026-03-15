<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Registry;

use Coderstm\PageBuilder\Schema\SectionSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * Parses a layout Blade file for @sections('key') directives and builds a
 * default layout data structure split into header and footer zones.
 *
 * The resulting structure stored in page JSON (and returned by the API) is:
 *
 *   layout: {
 *     type: "page",
 *     header: { sections: { "header": {...} }, order: ["header"] },
 *     footer: { sections: { "footer": {...} }, order: ["footer"] }
 *   }
 *
 * Usage:
 *   $parser = app(LayoutParser::class);
 *   $default = $parser->defaultLayout('page');
 */
class LayoutParser
{
    /**
     * Cache of parsed results keyed by layout type.
     *
     * @var array<string, array>
     */
    protected array $cache = [];

    public function __construct(
        protected readonly SectionRegistry $sectionRegistry,
    ) {}

    /**
     * Build the default layout data for a given layout type.
     *
     * Scans the Blade file for @sections() directives, splits them into
     * header (before @yield('content')) and footer (after @yield('content'))
     * zones, and resolves schema defaults for each key from SectionRegistry.
     *
     * @param  string  $layoutType  Layout view name (default: "page")
     * @return array{type: string, header: array, footer: array}
     */
    public function defaultLayout(string $layoutType = 'page'): array
    {
        if (isset($this->cache[$layoutType])) {
            return $this->cache[$layoutType];
        }

        ['header' => $headerKeys, 'footer' => $footerKeys] = $this->extractZonedKeys($layoutType);

        $layout = [
            'type' => $layoutType,
            'header' => $this->buildZone($headerKeys),
            'footer' => $this->buildZone($footerKeys),
        ];

        $this->cache[$layoutType] = $layout;

        return $layout;
    }

    /**
     * Build a zone array from a list of section keys.
     *
     * Each section is initialised with schema-derived default settings so that
     * pages whose JSON has no `layout` key render correctly with the theme defaults.
     * When the section schema declares presets, `presets[0]` is applied as the
     * initial settings/blocks so layout sections (header, footer) render with
     * content on first load rather than as empty shells.
     *
     * `blocks` is stored as an empty array (not stdClass) to avoid type errors
     * when the data flows into Renderer::hydrateBlocks(array $rawBlocks, …).
     *
     * @param  array<string>  $keys
     * @return array{sections: array, order: array}
     */
    protected function buildZone(array $keys): array
    {
        $sections = [];

        foreach ($keys as $key) {
            $sections[$key] = $this->buildSectionDefaults($key);
        }

        return [
            'sections' => $sections,
            'order' => $keys,
        ];
    }

    /**
     * Build the default section data for a single layout key.
     *
     * Falls back to a bare skeleton when no schema or preset is found.
     *
     * @return array<string, mixed>
     */
    protected function buildSectionDefaults(string $key): array
    {
        $base = [
            'type' => $key,
            'disabled' => false,
        ];

        $meta = $this->sectionRegistry->get($key);
        $schema = $meta['schema'] ?? null;

        if (! ($schema instanceof SectionSchema) || empty($schema->presets)) {
            return $base;
        }

        $preset = $schema->presets[0];

        if (! empty($preset['settings']) && is_array($preset['settings'])) {
            $base['settings'] = $preset['settings'];
        }

        if (! empty($preset['blocks']) && is_array($preset['blocks'])) {
            ['blocks' => $blocks, 'order' => $order] = $this->convertPresetBlocks($preset['blocks']);
            $base['blocks'] = $blocks;
            $base['order'] = $order;
        }

        return $base;
    }

    /**
     * Convert a preset blocks list (sequential array) into the keyed-map + order
     * format used by page JSON and Renderer::hydrateBlocks().
     *
     * Nested blocks are converted recursively.
     *
     * @param  array<int, array>  $presetBlocks
     * @return array{blocks: array<string, array>, order: array<string>}
     */
    protected function convertPresetBlocks(array $presetBlocks): array
    {
        $blocks = [];
        $order = [];
        $typeCounts = [];

        foreach ($presetBlocks as $presetBlock) {
            $type = $presetBlock['type'] ?? 'block';
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
            $id = $type . '_' . $typeCounts[$type];

            $block = [
                'type' => $type,
                'settings' => $presetBlock['settings'] ?? [],
            ];

            if (! empty($presetBlock['blocks']) && is_array($presetBlock['blocks'])) {
                ['blocks' => $nested, 'order' => $nestedOrder] = $this->convertPresetBlocks($presetBlock['blocks']);
                $block['blocks'] = $nested;
                $block['order'] = $nestedOrder;
            }

            $blocks[$id] = $block;
            $order[] = $id;
        }

        return ['blocks' => $blocks, 'order' => $order];
    }

    /**
     * Extract @sections('key') slot names from a layout Blade file, split
     * into "header" (before @yield('content')) and "footer" (after) zones.
     *
     * @return array{header: array<string>, footer: array<string>}
     */
    public function extractZonedKeys(string $layoutType): array
    {
        $filePath = $this->resolveLayoutPath($layoutType);

        if ($filePath === null || ! File::exists($filePath)) {
            return ['header' => [], 'footer' => []];
        }

        $content = File::get($filePath);

        // Split at @yield('content') boundary (supports single and double quotes).
        $parts = preg_split('/@yield\s*\(\s*[\'"]content[\'"]\s*\)/i', $content, 2);

        $before = $parts[0] ?? '';
        $after = $parts[1] ?? '';

        return [
            'header' => $this->matchSectionKeys($before),
            'footer' => $this->matchSectionKeys($after),
        ];
    }

    /**
     * Match all @sections('key') occurrences in a string fragment.
     *
     * @return array<string>
     */
    protected function matchSectionKeys(string $fragment): array
    {
        preg_match_all('/@sections\(\s*[\'"]([a-z0-9_\-]+)[\'"]\s*\)/i', $fragment, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Resolve the absolute file path for a layout Blade view.
     *
     * Tries the theme view finder first, then falls back to scanning
     * registered view paths directly.
     */
    protected function resolveLayoutPath(string $layoutType): ?string
    {
        $viewName = "layouts.{$layoutType}";

        try {
            if (View::exists($viewName)) {
                $path = View::getFinder()->find($viewName);

                if (File::exists($path)) {
                    return $path;
                }
            }
        } catch (\Throwable) {
            // Fall through to raw path scan
        }

        // Fallback: scan registered view hints/paths for the file directly
        foreach (config('view.paths', []) as $basePath) {
            $candidate = rtrim($basePath, '/')."/layouts/{$layoutType}.blade.php";
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Invalidate the internal cache (useful after theme switching).
     */
    public function flush(): void
    {
        $this->cache = [];
    }
}
