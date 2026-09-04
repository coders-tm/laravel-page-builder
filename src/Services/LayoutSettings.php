<?php

declare(strict_types=1);

namespace PageBuilder\Services;

use Illuminate\Support\Facades\File;
use PageBuilder\Registry\LayoutParser;

/**
 * Manages shared layout configurations stored in settings.json.
 *
 * Layout configs live under the `_pagebuilder.layouts` key, keyed by
 * layout type (e.g. "page", "simple"). Each layout contains header/footer
 * zones with sections and render order.
 *
 * The `layout` field in page JSON can be either:
 *  - A string (layout type) — meaning "use shared layout from here"
 *  - An object (full layout) — meaning "page-specific override"
 */
class LayoutSettings
{
    protected string $valuesPath;

    protected ?array $cached = null;

    public function __construct()
    {
        $this->valuesPath = config('pagebuilder.theme_settings_path');
    }

    /**
     * Get all layout configs.
     *
     * @return array<string, array{header: array, footer: array}>
     */
    public function all(): array
    {
        return $this->loadRaw()['layouts'] ?? [];
    }

    /**
     * Get a specific layout config by type.
     *
     * @return array{header: array, footer: array}|[]
     */
    public function get(string $layoutType): array
    {
        return $this->all()[$layoutType] ?? [];
    }

    /**
     * Save/overwrite a specific layout config.
     *
     * Flushes the LayoutParser cache after saving since layout
     * config changes may affect rendering.
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
     * Delete a specific layout config.
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
     * Load the raw `_pagebuilder` data from settings.json.
     *
     * @return array<string, mixed>
     */
    private function loadRaw(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        if (! File::exists($this->valuesPath)) {
            $this->cached = [];

            return [];
        }

        $data = json_decode(File::get($this->valuesPath), true);

        if (! is_array($data)) {
            $this->cached = [];

            return [];
        }

        $this->cached = $data['_pagebuilder'] ?? [];

        return $this->cached;
    }

    /**
     * Persist `_pagebuilder` data to settings.json, preserving other keys.
     */
    private function persist(array $data): bool
    {
        $dir = dirname($this->valuesPath);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $existing = [];
        if (File::exists($this->valuesPath)) {
            $existing = json_decode(File::get($this->valuesPath), true);
            if (! is_array($existing)) {
                $existing = [];
            }
        }

        $existing['_pagebuilder'] = $data;

        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = File::put($this->valuesPath, $json) !== false;

        if ($result) {
            $this->cached = $data;
        }

        return $result;
    }

    /**
     * Invalidate the cached layout data.
     */
    public function flush(): void
    {
        $this->cached = null;
    }
}
