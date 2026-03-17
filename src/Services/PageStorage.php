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
    protected string $pagesPath;

    public function __construct(protected readonly PageCache $pageCache)
    {
        $this->pagesPath = config('pagebuilder.pages');
    }

    /**
     * Load and decode a page JSON file by slug.
     */
    public function load(string $slug): ?PageData
    {
        $filePath = $this->pagesPath.'/'.$slug.'.json';

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
        if (! File::isDirectory($this->pagesPath)) {
            File::makeDirectory($this->pagesPath, 0755, true);
        }

        $payload = $data instanceof PageData ? $data->toArray() : $data;

        // Strip DB-only fields — title and meta are persisted to the database, not the JSON file.
        // Except for preserved slugs (like home), which don't have a database record.
        if (! PageBuilder::isPreservedPage($slug)) {
            unset($payload['title'], $payload['meta']);
        }

        $filePath = $this->pagesPath.'/'.$slug.'.json';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $result = File::put($filePath, $json) !== false;

        if ($result) {
            $this->pageCache->forget($slug);
        }

        return $result;
    }
}
