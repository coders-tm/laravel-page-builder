<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Services;

use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Support\PageData;
use Illuminate\Support\Facades\File;

/**
 * Handles loading and persisting page JSON data to disk.
 */
class PageStorage
{
    public function __construct() {}

    /**
     * Load and decode a page JSON file by slug.
     */
    public function load(string $slug): ?PageData
    {
        $filePath = $this->path($slug, 'json');

        if (! File::exists($filePath)) {
            return null;
        }

        $data = json_decode(File::get($filePath), true);

        if (! is_array($data)) {
            return null;
        }

        return PageData::fromArray($data);
    }

    /**
     * Persist a page's JSON data to disk, creating the pages directory if needed.
     */
    public function save(string $slug, array|PageData $data): bool
    {
        $filePath = $this->path($slug, 'json');
        $directory = dirname($filePath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $payload = $data instanceof PageData ? $data->toArray() : $data;

        // Strip DB-only fields — title and meta are persisted to the database, not the JSON file.
        // Except for preserved slugs (like home), which don't have a database record.
        if (! PageBuilder::isPreservedPage($slug)) {
            unset($payload['title'], $payload['meta']);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return File::put($filePath, $json) !== false;
    }

    /**
     * Get the absolute path for a page file.
     */
    private function path(string $slug, string $extension): string
    {
        return config('pagebuilder.pages').'/'.$slug.'.'.$extension;
    }
}
