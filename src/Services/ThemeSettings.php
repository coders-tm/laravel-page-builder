<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Services;

use Illuminate\Support\Facades\File;

/**
 * Manages global theme settings.
 *
 * Schema defines available settings (type, label, default, etc.).
 * Values are persisted to a JSON file on disk.
 */
class ThemeSettings
{
    protected string $valuesPath;

    protected ?array $cachedValues = null;

    public function __construct(protected readonly PageCache $pageCache)
    {
        $this->valuesPath = config('pagebuilder.theme_settings_path');
    }

    /**
     * Return the theme settings schema as defined in config.
     *
     * @return array<int, array{name: string, settings: array}>
     */
    public function schema(): array
    {
        return config('pagebuilder.theme_settings_schema', []);
    }

    /**
     * Load the current theme settings values from disk.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        if ($this->cachedValues !== null) {
            return $this->cachedValues;
        }

        if (! File::exists($this->valuesPath)) {
            $this->cachedValues = [];

            return [];
        }

        $data = json_decode(File::get($this->valuesPath), true);
        $this->cachedValues = is_array($data) ? $data : [];

        return $this->cachedValues;
    }

    /**
     * Persist theme settings values to disk.
     */
    public function save(array $values): bool
    {
        $dir = dirname($this->valuesPath);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $json = json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = File::put($this->valuesPath, $json) !== false;

        if ($result) {
            $this->cachedValues = $values;
            $this->pageCache->flush();
        }

        return $result;
    }

    /**
     * Get a single theme setting value by key, with an optional default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values()[$key] ?? $default;
    }

    /**
     * Allow property-style access: $theme->primary_color
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Return the merged schema + current values for the editor.
     *
     * @return array{schema: array, values: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'schema' => $this->schema(),
            'values' => $this->values(),
        ];
    }
}
