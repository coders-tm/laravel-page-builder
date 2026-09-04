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
use PageBuilder\Contracts\SettingsStoreInterface;

/**
 * Handles low-level JSON persistence and caching for settings stored under the
 * root `_pagebuilder` key in settings.json. Preserves all other top-level keys.
 */
class SettingsStore implements SettingsStoreInterface
{
    /**
     * The root JSON key for PageBuilder data.
     */
    public const ROOT_KEY = '_pagebuilder';

    /**
     * Cached raw file data array.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $cachedData = null;

    /**
     * Cached file path.
     */
    protected ?string $cachedPath = null;

    public function valuesPath(): string
    {
        return config('pagebuilder.theme_settings_path') ?? resource_path('settings.json');
    }

    public function get(string $section, ?string $key = null, mixed $default = null): mixed
    {
        $pagebuilder = $this->loadRootData();
        $sectionData = $pagebuilder[$section] ?? null;

        if ($key === null) {
            return $sectionData ?? $default;
        }

        if (! is_array($sectionData)) {
            return $default;
        }

        return $sectionData[$key] ?? $default;
    }

    public function set(string $section, array|string $key, mixed $value = null): bool
    {
        $root = $this->loadFullFile();

        if (! isset($root[self::ROOT_KEY]) || ! is_array($root[self::ROOT_KEY])) {
            $root[self::ROOT_KEY] = [];
        }

        if (is_array($key)) {
            $root[self::ROOT_KEY][$section] = $key;
        } else {
            if (! isset($root[self::ROOT_KEY][$section]) || ! is_array($root[self::ROOT_KEY][$section])) {
                $root[self::ROOT_KEY][$section] = [];
            }
            $root[self::ROOT_KEY][$section][$key] = $value;
        }

        return $this->persist($root);
    }

    public function forget(string $section, ?string $key = null): bool
    {
        $root = $this->loadFullFile();

        if (! isset($root[self::ROOT_KEY]) || ! is_array($root[self::ROOT_KEY])) {
            return true;
        }

        if ($key === null) {
            unset($root[self::ROOT_KEY][$section]);
        } else {
            if (isset($root[self::ROOT_KEY][$section]) && is_array($root[self::ROOT_KEY][$section])) {
                unset($root[self::ROOT_KEY][$section][$key]);
            }
        }

        return $this->persist($root);
    }

    public function all(): array
    {
        return $this->loadRootData();
    }

    public function flush(): void
    {
        $this->cachedData = null;
        $this->cachedPath = null;
    }

    /**
     * Load only the `_pagebuilder` array from disk/cache.
     *
     * @return array<string, mixed>
     */
    protected function loadRootData(): array
    {
        $full = $this->loadFullFile();

        return is_array($full[self::ROOT_KEY] ?? null) ? $full[self::ROOT_KEY] : [];
    }

    /**
     * Load the entire JSON file array from disk/cache.
     *
     * @return array<string, mixed>
     */
    protected function loadFullFile(): array
    {
        $path = $this->valuesPath();

        if ($this->cachedData !== null && $this->cachedPath === $path) {
            return $this->cachedData;
        }

        if (! File::exists($path)) {
            $this->cachedData = [];
            $this->cachedPath = $path;

            return [];
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            $this->cachedData = [];
            $this->cachedPath = $path;

            return [];
        }

        $this->cachedData = $data;
        $this->cachedPath = $path;

        return $this->cachedData;
    }

    /**
     * Persist full array structure to disk.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): bool
    {
        $path = $this->valuesPath();

        File::ensureDirectoryExists(dirname($path), 0755, true);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = File::put($path, $json) !== false;

        if ($result) {
            $this->cachedData = $data;
            $this->cachedPath = $path;
        }

        return $result;
    }
}
