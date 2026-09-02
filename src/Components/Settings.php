<?php

declare(strict_types=1);

namespace PageBuilder\Components;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Schema-aware settings bag for runtime section/block data.
 *
 * Merges stored values with schema-defined defaults. Accessing any key
 * returns the stored value if present, otherwise the schema default.
 *
 * Blade usage:
 *
 *   {{ $section->settings->heading }}
 *   {{ $section->settings->get('columns', 3) }}
 *
 * @template-implements ArrayAccess<string, mixed>
 */
final class Settings implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    /**
     * Stored setting values keyed by setting id.
     *
     * @var array<string, mixed>
     */
    private array $values;

    /**
     * Schema-defined defaults keyed by setting id.
     *
     * @var array<string, mixed>
     */
    private array $defaults;

    /**
     * @param  array<string, mixed>  $values  Raw stored values (from page JSON).
     * @param  array<string, mixed>  $defaults  Pre-computed defaults (from schema).
     */
    public function __construct(array $values = [], array $defaults = [])
    {
        $this->values = $values;
        $this->defaults = $defaults;
    }

    /**
     * Create a Settings instance from raw schema definitions.
     *
     * @param  array<string, mixed>  $values
     * @param  array<int|string, array>  $schema  Raw schema settings array.
     */
    public static function fromSchema(array $values = [], array $schema = []): self
    {
        $defaults = [];

        foreach ($schema as $entry) {
            $id = is_array($entry) ? ($entry['id'] ?? null) : ($entry->id ?? null);
            $default = is_array($entry) ? ($entry['default'] ?? null) : ($entry->default ?? null);

            if ($id !== null && $id !== '') {
                $defaults[$id] = $default;
            }
        }

        return new self($values, $defaults);
    }

    /**
     * Resolve a setting value: stored value → schema default → caller fallback.
     */
    public function get(string $key, mixed $fallback = null): mixed
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        if (array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }

        return $fallback;
    }

    /**
     * Check whether the setting exists in stored values or schema.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values)
            || array_key_exists($key, $this->defaults);
    }

    /**
     * Return all settings as a flat key → resolved-value map.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->defaults, $this->values);
    }

    /**
     * Return only the raw stored values (without defaults).
     *
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->values;
    }

    /**
     * Return only the schema defaults.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return $this->defaults;
    }

    // ─── Magic access ────────────────────────────────────────────

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * Allow `$section->settings('key', $fallback)` callable syntax.
     */
    public function __invoke(string $key, mixed $fallback = null): mixed
    {
        return $this->get($key, $fallback);
    }

    public function __toString(): string
    {
        return '';
    }

    // ─── ArrayAccess ─────────────────────────────────────────────

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->values[(string) $offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->values[(string) $offset]);
    }

    // ─── Serialization ──────────────────────────────────────────

    public function toArray(): array
    {
        return $this->all();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): mixed
    {
        return $this->all();
    }
}
