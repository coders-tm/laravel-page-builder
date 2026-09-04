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
use PageBuilder\PageBuilder;
use PageBuilder\Support\LayoutConfig;
use PageBuilder\Support\PageData;

/**
 * Handles loading and persisting page JSON data to disk.
 *
 * Also manages layout splitting: when saving, layout data is split
 * between the page JSON (page-specific) and LayoutSettings (shared).
 */
class PageStorage
{
    public function __construct(
        protected readonly LayoutSettings $layoutSettings,
    ) {}

    /**
     * Load and decode a page JSON file by slug.
     *
     * When a language is set, tries locale-specific files first
     * (e.g. pages/{slug}.fr.json) before falling back to the default.
     */
    public function load(string $slug): ?PageData
    {
        $data = $this->loadRaw($slug);

        if ($data === null) {
            return null;
        }

        return PageData::fromArray($data);
    }

    /**
     * Load raw page JSON data without converting to PageData.
     *
     * Returns the decoded JSON array, or null if the file doesn't exist.
     * When a language is set, tries locale-specific files first
     * (e.g. pages/{slug}.fr.json) before falling back to the default.
     *
     * @return array<string, mixed>|null
     */
    public function loadRaw(string $slug): ?array
    {
        $filePath = $this->resolvePath($slug);

        if (! File::exists($filePath)) {
            return null;
        }

        $data = json_decode(File::get($filePath), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Persist a page's JSON data to disk, creating the pages directory if needed.
     *
     * When a language is set, saves to the locale-specific path
     * (e.g. pages/{slug}.fr.json). Layout splitting is based on the existing page.json:
     *  - No layout in existing page.json → save layout to LayoutSettings
     *  - Existing layout is a string → save layout to LayoutSettings
     *  - Existing layout is an object → save layout to page.json
     */
    public function save(string $slug, array|PageData $data): bool
    {
        $filePath = $this->resolveSavePath($slug);
        $directory = dirname($filePath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $payload = $data instanceof PageData ? $data->toArray() : $data;

        // Handle layout splitting based on existing page.json.
        $payload = $this->splitLayout($payload, $slug);

        // Strip DB-only fields — title and meta are persisted to the database, not the JSON file.
        // Except for preserved slugs (like home), which don't have a database record.
        if (! PageBuilder::isPreservedPage($slug)) {
            unset($payload['title'], $payload['meta']);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return File::put($filePath, $json) !== false;
    }

    /**
     * Split layout data between page JSON and LayoutSettings.
     *
     * Decision is based on the EXISTING page.json's layout value:
     *  - No layout exists → save config to LayoutSettings, store type string in page JSON
     *  - Existing layout is a string → save config to LayoutSettings, store type string in page JSON
     *  - Existing layout is an object → save full layout object to page JSON
     *
     * @param  array<string, mixed>  $data  The new data to save
     * @param  string  $slug  The page slug
     * @return array<string, mixed>
     */
    private function splitLayout(array $data, string $slug): array
    {
        $incomingLayout = $data['layout'] ?? null;

        // Load existing page.json to check what's already stored.
        $existing = $this->loadRaw($slug);
        $existingLayout = $existing['layout'] ?? null;

        // Determine save target based on existing layout:
        //  - No existing layout or existing is string → save to LayoutSettings
        //  - Existing is object → save to page.json
        $saveToLayoutSettings = ! is_array($existingLayout);

        if ($saveToLayoutSettings) {
            // Save to LayoutSettings, store only type string in page JSON.
            $layoutType = is_array($incomingLayout)
                ? ($incomingLayout['type'] ?? 'page')
                : (is_string($incomingLayout) ? $incomingLayout : 'page');

            if (is_array($incomingLayout) && isset($incomingLayout['type'])) {
                $config = $incomingLayout;
                unset($config['source']);
                $this->layoutSettings->save($layoutType, LayoutConfig::fromArray($config));
            }

            $data['layout'] = $layoutType;
        } else {
            // Save to page.json — strip the editor-only `source` key.
            if (is_array($data['layout'] ?? null)) {
                unset($data['layout']['source']);
            }
        }

        return $data;
    }

    /**
     * Get the absolute path for a page file, with language-aware resolution.
     *
     * When a language is set, checks for the locale-specific file first
     * (e.g. pages/{slug}.fr.json), falling back to the default (pages/{slug}.json).
     */
    private function resolvePath(string $slug): string
    {
        $lang = PageBuilder::getLang();

        if ($lang !== null) {
            $localePath = $this->buildPath($slug, 'json', $lang);

            if (File::exists($localePath)) {
                return $localePath;
            }
        }

        return $this->buildPath($slug, 'json');
    }

    /**
     * Get the absolute path for saving a page file.
     *
     * When a language is set, saves to the locale-specific path.
     * Otherwise saves to the default path.
     */
    private function resolveSavePath(string $slug): string
    {
        $lang = PageBuilder::getLang();

        if ($lang !== null) {
            return $this->buildPath($slug, 'json', $lang);
        }

        return $this->buildPath($slug, 'json');
    }

    /**
     * Build the absolute path for a page file.
     */
    private function buildPath(string $slug, string $extension, ?string $lang = null): string
    {
        $base = config('pagebuilder.pages').'/'.$slug;

        if ($lang !== null) {
            $base .= '.'.$lang;
        }

        return $base.'.'.$extension;
    }
}
