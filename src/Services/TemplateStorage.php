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

namespace PageBuilder\Services;

use Illuminate\Support\Facades\File;
use PageBuilder\Facades\Theme;
use PageBuilder\PageBuilder;

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
    private readonly string $templatesPath;

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
     * Get all available templates from the storage and theme.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function all(): array
    {
        $templates = collect();

        // 1. Scan default templates path
        if (File::isDirectory($this->templatesPath)) {
            $templates = $templates->merge($this->scanDirectory($this->templatesPath));
        }

        // 2. Scan active theme path
        try {
            $themePath = Theme::path('views/templates');
            if ($themePath !== null && File::isDirectory($themePath)) {
                $templates = $templates->merge($this->scanDirectory($themePath));
            }
        } catch (\Throwable) {
            // Theme service might not be available or no active theme
        }

        return $templates->unique('value')->values()->toArray();
    }

    /**
     * Scan a directory for .json template files.
     */
    private function scanDirectory(string $path): array
    {
        return collect(File::files($path))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(fn ($file) => [
                'label' => str(basename($file->getFilename(), '.json'))->title()->replace('-', ' ')->value(),
                'value' => basename($file->getFilename(), '.json'),
            ])
            ->toArray();
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
     * Resolution order (first match wins):
     *   1. Active theme's views/templates/{name}.{lang}.json (when language is set)
     *   2. Active theme's views/templates/{name}.json
     *   3. config('pagebuilder.templates')/{name}.{lang}.json (when language is set)
     *   4. config('pagebuilder.templates')/{name}.json
     */
    private function resolvePath(string $name): ?string
    {
        $lang = PageBuilder::getLang();

        // 1. Active theme path (locale-specific first, then default)
        $themePath = $this->resolveThemePath($name, $lang);
        if ($themePath !== null) {
            return $themePath;
        }

        // 2. Default templates path (locale-specific first, then default)
        if ($lang !== null) {
            $localePath = rtrim($this->templatesPath, '/').'/'.str_replace('..', '', $name).'.'.$lang.'.json';
            if (File::exists($localePath)) {
                return $localePath;
            }
        }

        $path = rtrim($this->templatesPath, '/').'/'.str_replace('..', '', $name).'.json';

        return File::exists($path) ? $path : null;
    }

    /**
     * Attempt to resolve the template from the active theme.
     *
     * Checks locale-specific template first when a language is set.
     */
    private function resolveThemePath(string $name, ?string $lang = null): ?string
    {
        try {
            // Check locale-specific template first
            if ($lang !== null) {
                $localePath = Theme::path('views/templates/'.$name.'.'.$lang.'.json');
                if ($localePath !== null && File::exists($localePath)) {
                    return $localePath;
                }
            }

            $themePath = Theme::path('views/templates/'.$name.'.json');

            return ($themePath !== null && File::exists($themePath)) ? $themePath : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
