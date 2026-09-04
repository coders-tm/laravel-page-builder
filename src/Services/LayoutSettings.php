<?php

declare(strict_types=1);

namespace PageBuilder\Services;

use Illuminate\Support\Facades\File;
use PageBuilder\Registry\LayoutParser;

/**
 * Manages shared layout configurations stored in settings.json.
 *
 * Layout configurations live under the `_pagebuilder.layouts` key within
 * the settings file, keyed by layout type (e.g., "page", "simple").
 * Each layout definition contains header and footer zones with sections
 * and their rendering order.
 *
 * The `layout` property in page JSON documents can be either:
 *  - A string (layout type) — indicating "use shared layout from here"
 *  - An object (full layout definition) — indicating a "page-specific layout override"
 */
class LayoutSettings
{
    /**
     * The root JSON storage key for PageBuilder data.
     *
     * @var string
     */
    public const CONFIG_KEY = '_pagebuilder';

    /**
     * The cached raw `_pagebuilder` settings data.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $cached = null;

    /**
     * The file path of the currently cached settings file.
     */
    protected ?string $cachedPath = null;

    /**
     * Get the resolved path to the settings JSON file.
     */
    protected function valuesPath(): string
    {
        return config('pagebuilder.theme_settings_path') ?? resource_path('settings.json');
    }

    /**
     * Get all shared layout configurations.
     *
     * @return array<string, array{header: array, footer: array}>
     */
    public function all(): array
    {
        return $this->loadRaw()['layouts'] ?? [];
    }

    /**
     * Get a specific layout configuration by its layout type.
     *
     * @return array{header: array, footer: array}|array{}
     */
    public function get(string $layoutType): array
    {
        return $this->all()[$layoutType] ?? [];
    }

    /**
     * Save or overwrite a layout configuration for the given layout type.
     *
     * Automatically flushes the LayoutParser cache after saving to ensure
     * updated layout structures take immediate effect during rendering.
     *
     * @param  array<string, mixed>  $config
     */
    public function save(string $layoutType, array $config): bool
    {
        $raw = $this->loadRaw();
        $raw['layouts'][$layoutType] = $config;

        $result = $this->persist($raw);

        if ($result) {
            app(LayoutParser::class)->flush();
        }

        return $result;
    }

    /**
     * Delete a specific layout configuration by layout type.
     *
     * Flushes the LayoutParser cache after removal.
     */
    public function delete(string $layoutType): bool
    {
        $raw = $this->loadRaw();
        unset($raw['layouts'][$layoutType]);

        $result = $this->persist($raw);

        if ($result) {
            app(LayoutParser::class)->flush();
        }

        return $result;
    }

    /**
     * Load the raw `_pagebuilder` root data array from disk.
     *
     * @return array<string, mixed>
     */
    private function loadRaw(): array
    {
        $path = $this->valuesPath();

        if ($this->cached !== null && $this->cachedPath === $path) {
            return $this->cached;
        }

        if (! File::exists($path)) {
            $this->cached = [];
            $this->cachedPath = $path;

            return [];
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            $this->cached = [];
            $this->cachedPath = $path;

            return [];
        }

        $this->cached = $data[self::CONFIG_KEY] ?? [];
        $this->cachedPath = $path;

        return $this->cached;
    }

    /**
     * Persist `_pagebuilder` data to settings.json while preserving all other root keys.
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(array $data): bool
    {
        $valuesPath = $this->valuesPath();

        File::ensureDirectoryExists(dirname($valuesPath), 0755, true);

        $existing = [];
        if (File::exists($valuesPath)) {
            $existing = json_decode(File::get($valuesPath), true);
            if (! is_array($existing)) {
                $existing = [];
            }
        }

        $existing[self::CONFIG_KEY] = $data;

        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = File::put($valuesPath, $json) !== false;

        if ($result) {
            $this->cached = $data;
            $this->cachedPath = $valuesPath;
        }

        return $result;
    }

    /**
     * Invalidate the in-memory cached layout data.
     */
    public function flush(): void
    {
        $this->cached = null;
    }
}
