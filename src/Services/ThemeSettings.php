<?php

declare(strict_types=1);

namespace PageBuilder\Services;

use Illuminate\Support\Facades\File;

/**
 * Manages global theme settings stored in settings.json.
 *
 * Theme setting definitions (type, label, default, etc.) are declared in the
 * `pagebuilder.theme_settings_schema` configuration array. Current theme values
 * are persisted under the `_pagebuilder.theme` key in settings.json.
 */
class ThemeSettings
{
    /**
     * The root JSON storage key for PageBuilder data.
     *
     * @var string
     */
    public const CONFIG_KEY = '_pagebuilder';

    /**
     * The cached theme setting values loaded from disk.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $cachedValues = null;

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
     * Return the theme settings schema as defined in application configuration.
     *
     * @return array<int, array{name: string, settings: array}>
     */
    public function schema(): array
    {
        return config('pagebuilder.theme_settings_schema', []);
    }

    /**
     * Load the current theme settings values from disk under `_pagebuilder.theme`.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $path = $this->valuesPath();

        if ($this->cachedValues !== null && $this->cachedPath === $path) {
            return $this->cachedValues;
        }

        if (! File::exists($path)) {
            $this->cachedValues = [];
            $this->cachedPath = $path;

            return [];
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            $this->cachedValues = [];
            $this->cachedPath = $path;

            return [];
        }

        $themeValues = $data[self::CONFIG_KEY]['theme'] ?? [];

        $this->cachedValues = is_array($themeValues) ? $themeValues : [];
        $this->cachedPath = $path;

        return $this->cachedValues;
    }

    /**
     * Persist theme settings values to disk under `_pagebuilder.theme`.
     *
     * Preserves other keys within `_pagebuilder` (such as layouts) as well as
     * top-level keys in the settings file.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): bool
    {
        $valuesPath = $this->valuesPath();

        File::ensureDirectoryExists(dirname($valuesPath), 0755, true);

        // Read existing content to preserve other keys
        $existing = [];
        if (File::exists($valuesPath)) {
            $existing = json_decode(File::get($valuesPath), true);
            if (! is_array($existing)) {
                $existing = [];
            }
        }

        if (! isset($existing[self::CONFIG_KEY]) || ! is_array($existing[self::CONFIG_KEY])) {
            $existing[self::CONFIG_KEY] = [];
        }

        $existing[self::CONFIG_KEY]['theme'] = $values;

        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = File::put($valuesPath, $json) !== false;

        if ($result) {
            $this->cachedValues = $values;
            $this->cachedPath = $valuesPath;
        }

        return $result;
    }

    /**
     * Invalidate the in-memory cached theme settings data.
     */
    public function flush(): void
    {
        $this->cachedValues = null;
        $this->cachedPath = null;
    }

    /**
     * Get a single theme setting value by key, with an optional default fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values()[$key] ?? $default;
    }

    /**
     * Allow property-style access to theme settings (e.g. $theme->primary_color).
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Return the combined schema and current values formatted for the editor.
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
