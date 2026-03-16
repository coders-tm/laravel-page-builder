<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Services;

use Illuminate\Support\Facades\File;

/**
 * Loads JSON template files from the templates directory.
 *
 * Resolution order (first match wins):
 *   1. Active theme's views/templates/{name}.json
 *   2. config('pagebuilder.templates')/{name}.json
 *
 * Template JSON schema:
 *   {
 *     "layout":   string|false   // layout type (e.g. "page") or false for no layout
 *     "wrapper":  string         // CSS-selector wrapper (e.g. "div#id.class[attr=val]")
 *     "sections": { ... }        // section data map (same format as page JSON)
 *     "order":    [ ... ]        // render order
 *   }
 *
 * A Blade file (pages/{slug}.blade.php) or a page JSON (pages/{slug}.json)
 * always takes priority over a template.
 *
 * Template naming: page.json, page.alternate.json
 * A template can only exist as JSON, not as a Blade file.
 */
final class TemplateStorage
{
    protected string $templatesPath;

    public function __construct()
    {
        $this->templatesPath = (string) config('pagebuilder.templates', resource_path('views/templates'));
    }

    /**
     * Load a template by name, returning its raw decoded JSON array or null when not found.
     *
     * @param  string  $name  Template name without extension (e.g. "page", "page.alternate")
     * @return array<string, mixed>|null
     */
    public function load(string $name): ?array
    {
        $name = $this->normalizeName($name);
        $filePath = $this->resolvePath($name);

        if ($filePath === null) {
            return null;
        }

        $data = json_decode(File::get($filePath), true);

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Normalize the template name: trim, lowercase, strip trailing .json.
     */
    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));

        // Strip .json suffix if caller included it
        if (str_ends_with($name, '.json')) {
            $name = substr($name, 0, -5);
        }

        return $name !== '' ? $name : 'page';
    }

    /**
     * Resolve the absolute file path for the template.
     *
     * Checks the active theme first, then the configured templates directory.
     */
    private function resolvePath(string $name): ?string
    {
        // 1. Active theme path
        $themePath = $this->resolveThemePath($name);
        if ($themePath !== null) {
            return $themePath;
        }

        // 2. Default templates path
        $path = rtrim($this->templatesPath, '/').'/'.str_replace('..', '', $name).'.json';

        return File::exists($path) ? $path : null;
    }

    /**
     * Attempt to resolve the template from the active theme.
     */
    private function resolveThemePath(string $name): ?string
    {
        try {
            $themePath = Theme::path('views/templates/'.$name.'.json');

            return ($themePath !== null && File::exists($themePath)) ? $themePath : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
