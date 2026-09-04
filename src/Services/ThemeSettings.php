<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Services;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use PageBuilder\Contracts\SettingsStoreInterface;

/**
 * Manages global theme settings stored in settings.json via SettingsStoreInterface.
 *
 * Provides strict type-safe accessors (getString, getInt, getBool, getArray, etc.),
 * implements ArrayAccess, Arrayable, JsonSerializable, and Countable contracts,
 * and maintains full integration with Blade views and editor APIs.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 */
class ThemeSettings implements Arrayable, ArrayAccess, Countable, JsonSerializable
{
    public const STORAGE_SECTION = 'theme';

    protected readonly SettingsStoreInterface $store;

    public function __construct(?SettingsStoreInterface $store = null)
    {
        $this->store = $store ?? app(SettingsStoreInterface::class);
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
     * Load the current theme settings values from storage under `_pagebuilder.theme`.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $data = $this->store->get(self::STORAGE_SECTION);

        return is_array($data) ? $data : [];
    }

    /**
     * Persist theme settings values to storage under `_pagebuilder.theme`.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): bool
    {
        return $this->store->set(self::STORAGE_SECTION, $values);
    }

    /**
     * Invalidate in-memory cached theme settings data.
     */
    public function flush(): void
    {
        $this->store->flush();
    }

    /**
     * Check if a setting key exists.
     */
    public function has(string $key): bool
    {
        $values = $this->values();

        return array_key_exists($key, $values);
    }

    /**
     * Get a setting value by key, with fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->values();

        return $values[$key] ?? $default;
    }

    /**
     * Get setting value strictly as string.
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Get setting value strictly as integer.
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Get setting value strictly as float.
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Get setting value strictly as boolean.
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return is_bool($value) ? $value : (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Get setting value strictly as array.
     *
     * @return array<mixed>
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    /**
     * Set a single theme setting value and persist to storage.
     */
    public function set(string $key, mixed $value): static
    {
        $current = $this->values();
        $current[$key] = $value;
        $this->save($current);

        return $this;
    }

    /**
     * Merge and persist multiple settings values.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): static
    {
        $current = array_merge($this->values(), $values);
        $this->save($current);

        return $this;
    }

    /**
     * Allow property-style access in Blade views (e.g., $theme->primary_color).
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Build Google Fonts <link> tags for every google_font setting in the theme schema.
     *
     * Uses saved values when available, falling back to setting defaults.
     * Returns an empty string when no google_font settings are configured.
     */
    public function fontElements(): string
    {
        $fonts = [];

        foreach ($this->schema() as $group) {
            foreach ($group['settings'] ?? [] as $setting) {
                if (($setting['type'] ?? '') !== 'google_font') {
                    continue;
                }

                $key = $setting['key'] ?? null;
                $family = $key ? $this->getString($key, (string) ($setting['default'] ?? '')) : (string) ($setting['default'] ?? '');
                $family = trim($family);

                if ($family !== '') {
                    $fonts[] = $family;
                }
            }
        }

        $fonts = array_unique($fonts);

        if (empty($fonts)) {
            return '';
        }

        $query = implode('&', array_map(
            static fn (string $f) => 'family='.rawurlencode($f).':wght@400;500;600;700',
            $fonts
        ));

        $href = 'https://fonts.googleapis.com/css2?'.$query.'&display=swap';

        return implode("\n", [
            '<link rel="preconnect" href="https://fonts.googleapis.com">',
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
            '<link href="'.e($href).'" rel="stylesheet">',
        ]);
    }

    /**
     * Return the schema and current values array formatted for the editor.
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

    /**
     * Serialize to JSON array.
     *
     * @return array{schema: array, values: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Count the number of active setting values.
     */
    public function count(): int
    {
        return count($this->values());
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? $this->get($offset) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_string($offset)) {
            $this->set($offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            $current = $this->values();
            unset($current[$offset]);
            $this->save($current);
        }
    }
}
