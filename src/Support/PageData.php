<?php

declare(strict_types=1);

namespace PageBuilder\Support;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Immutable Data Transfer Object representing parsed page builder page data.
 *
 * Encapsulates body sections, render ordering, layout resolution (headers/footers),
 * title, PageMeta DTO, and wrapper attributes.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 */
final class PageData implements Arrayable, ArrayAccess, Countable, Jsonable, JsonSerializable
{
    private readonly PageMeta $metaObject;

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @param  array<int, string>  $order
     * @param  array<string, mixed>  $layout
     * @param  array<string, mixed>|PageMeta  $meta
     */
    public function __construct(
        private readonly array $sections = [],
        private readonly array $order = [],
        private readonly array $layout = [],
        private readonly string $title = '',
        array|PageMeta $meta = [],
        private readonly ?string $wrapper = null,
    ) {
        $this->metaObject = $meta instanceof PageMeta ? $meta : PageMeta::fromArray($meta);
    }

    /**
     * Create a PageData instance from a raw decoded page JSON array.
     *
     * Three-layer merge priority (lowest to highest):
     *  1. $defaultLayout — schema defaults from LayoutParser
     *  2. $sharedLayout  — shared config from LayoutSettings
     *  3. $data['layout'] — page-specific override from page JSON
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|LayoutConfig  $defaultLayout
     * @param  array<string, mixed>|LayoutConfig  $sharedLayout
     */
    public static function fromArray(
        array $data,
        array|LayoutConfig $defaultLayout = [],
        array|LayoutConfig $sharedLayout = [],
    ): self {
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $order = is_array($data['order'] ?? null) ? array_values($data['order']) : array_keys($sections);

        $defaultLayoutArr = $defaultLayout instanceof LayoutConfig ? $defaultLayout->toArray() : $defaultLayout;
        $sharedLayoutArr = $sharedLayout instanceof LayoutConfig ? $sharedLayout->toArray() : $sharedLayout;

        // Detect layout type string vs page-specific override object
        $layoutValue = $data['layout'] ?? null;

        if (is_string($layoutValue)) {
            $storedLayout = ['type' => $layoutValue];
            $layoutSource = 'shared';
        } elseif (is_array($layoutValue)) {
            $storedLayout = $layoutValue;
            $layoutSource = 'page';
        } else {
            $storedLayout = [];
            $layoutSource = 'shared';
        }

        $layout = self::mergeLayouts($defaultLayoutArr, $sharedLayoutArr, $storedLayout);

        if (! empty($layout)) {
            $layout['source'] = $layoutSource;
        }

        $wrapper = isset($data['wrapper']) && is_string($data['wrapper']) && $data['wrapper'] !== ''
            ? $data['wrapper']
            : null;

        $metaData = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        return new self(
            sections: $sections,
            order: $order,
            layout: $layout,
            title: (string) ($data['title'] ?? ''),
            meta: PageMeta::fromArray($metaData),
            wrapper: $wrapper,
        );
    }

    /**
     * Merge three layout layers: default, shared, and stored (per-page).
     *
     * @param  array<string, mixed>  $default
     * @param  array<string, mixed>  $shared
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function mergeLayouts(array $default, array $shared, array $stored): array
    {
        if (empty($default) && empty($shared) && empty($stored)) {
            return [];
        }

        $type = $stored['type'] ?? $shared['type'] ?? $default['type'] ?? 'page';

        return [
            'type' => $type,
            'header' => self::mergeZone(
                $default['header'] ?? [],
                $shared['header'] ?? [],
                $stored['header'] ?? [],
            ),
            'footer' => self::mergeZone(
                $default['footer'] ?? [],
                $shared['footer'] ?? [],
                $stored['footer'] ?? [],
            ),
        ];
    }

    /**
     * Merge a single zone (header or footer) from three layers.
     *
     * @param  array<string, mixed>  $default
     * @param  array<string, mixed>  $shared
     * @param  array<string, mixed>  $stored
     * @return array{sections: array<string, mixed>, order: array<int, string>}
     */
    private static function mergeZone(array $default, array $shared, array $stored): array
    {
        $defaultSections = $default['sections'] ?? [];
        $sharedSections = $shared['sections'] ?? [];
        $storedSections = $stored['sections'] ?? [];

        $sections = $defaultSections;

        foreach ($sharedSections as $key => $section) {
            if (isset($sections[$key])) {
                $sections[$key] = array_merge($sections[$key], $section, [
                    'settings' => array_merge(
                        $sections[$key]['settings'] ?? [],
                        $section['settings'] ?? [],
                    ),
                    'blocks' => $section['blocks'] ?? $sections[$key]['blocks'] ?? [],
                    'order' => $section['order'] ?? $sections[$key]['order'] ?? [],
                ]);
            } else {
                $sections[$key] = $section;
            }
        }

        foreach ($storedSections as $key => $section) {
            if (isset($sections[$key])) {
                $sections[$key] = array_merge($sections[$key], $section, [
                    'settings' => array_merge(
                        $sections[$key]['settings'] ?? [],
                        $section['settings'] ?? [],
                    ),
                    'blocks' => $section['blocks'] ?? $sections[$key]['blocks'] ?? [],
                    'order' => $section['order'] ?? $sections[$key]['order'] ?? [],
                ]);
            } else {
                $sections[$key] = $section;
            }
        }

        $order = $stored['order'] ?? $shared['order'] ?? $default['order'] ?? array_keys($sections);

        return [
            'sections' => $sections,
            'order' => array_values($order),
        ];
    }

    /**
     * Return ordered list of section IDs.
     *
     * @return array<int, string>
     */
    public function order(): array
    {
        return $this->order;
    }

    /**
     * Check if a body section exists.
     */
    public function hasSection(string $id): bool
    {
        return array_key_exists($id, $this->sections);
    }

    /**
     * Return section data for a given section ID, or null.
     *
     * @return array<string, mixed>|null
     */
    public function section(string $id): ?array
    {
        return $this->sections[$id] ?? null;
    }

    /**
     * Return all body sections map.
     *
     * @return array<string, array<string, mixed>>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * Return page title.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Return page SEO metadata as PageMeta DTO.
     */
    public function meta(): PageMeta
    {
        return $this->metaObject;
    }

    /**
     * Return raw layout structure.
     *
     * @return array<string, mixed>
     */
    public function layout(): array
    {
        return $this->layout;
    }

    /**
     * Return layout configuration as a type-safe LayoutConfig DTO.
     */
    public function layoutConfig(): LayoutConfig
    {
        return LayoutConfig::fromArray($this->layout);
    }

    /**
     * Return layout source: "shared" or "page".
     */
    public function layoutSource(): string
    {
        return (string) ($this->layout['source'] ?? 'shared');
    }

    /**
     * Return layout type identifier (defaults to "page").
     */
    public function layoutType(): string
    {
        return (string) ($this->layout['type'] ?? 'page');
    }

    /**
     * Return full Blade view name for layout.
     */
    public function layoutView(): string
    {
        return "layouts.{$this->layoutType()}";
    }

    /**
     * Return section data for a given layout key, or null if absent/disabled.
     *
     * @return array<string, mixed>|null
     */
    public function layoutSection(string $key): ?array
    {
        $raw = ($this->layout['header']['sections'] ?? [])[$key]
            ?? ($this->layout['footer']['sections'] ?? [])[$key]
            ?? null;

        if ($raw === null) {
            return null;
        }

        if (($raw['disabled'] ?? false) === true) {
            return null;
        }

        $raw['blocks'] = is_array($raw['blocks'] ?? null) ? $raw['blocks'] : [];
        $raw['order'] = is_array($raw['order'] ?? null) ? $raw['order'] : [];

        return $raw;
    }

    /**
     * Return all layout sections as a flattened map.
     *
     * @return array<string, array<string, mixed>>
     */
    public function layoutSections(): array
    {
        $all = array_merge(
            $this->layout['header']['sections'] ?? [],
            $this->layout['footer']['sections'] ?? [],
        );

        return array_map(function (array $raw): array {
            $raw['blocks'] = is_array($raw['blocks'] ?? null) ? $raw['blocks'] : [];
            $raw['order'] = is_array($raw['order'] ?? null) ? $raw['order'] : [];

            return $raw;
        }, $all);
    }

    /**
     * Return header zone structure.
     *
     * @return array{sections: array<string, mixed>, order: array<int, string>}
     */
    public function layoutHeader(): array
    {
        $zone = $this->layout['header'] ?? [];
        $sections = array_map(fn (array $s) => array_merge(['blocks' => [], 'order' => []], $s), $zone['sections'] ?? []);
        $order = $zone['order'] ?? [];

        if ($order === [] && $sections !== []) {
            $order = array_keys($sections);
        }

        return [
            'sections' => $sections,
            'order' => array_values($order),
        ];
    }

    /**
     * Return footer zone structure.
     *
     * @return array{sections: array<string, mixed>, order: array<int, string>}
     */
    public function layoutFooter(): array
    {
        $zone = $this->layout['footer'] ?? [];
        $sections = array_map(fn (array $s) => array_merge(['blocks' => [], 'order' => []], $s), $zone['sections'] ?? []);
        $order = $zone['order'] ?? [];

        if ($order === [] && $sections !== []) {
            $order = array_keys($sections);
        }

        return [
            'sections' => $sections,
            'order' => array_values($order),
        ];
    }

    /**
     * Return wrapper CSS selector string, or null.
     */
    public function wrapper(): ?string
    {
        return $this->wrapper;
    }

    /**
     * Determine if page has no body sections.
     */
    public function isEmpty(): bool
    {
        return $this->sections === [];
    }

    /**
     * Determine if page has at least one body section.
     */
    public function isNotEmpty(): bool
    {
        return $this->sections !== [];
    }

    /**
     * Count body sections.
     */
    public function count(): int
    {
        return count($this->sections);
    }

    /**
     * Convert to raw associative array structure.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $layout = [];

        if (! empty($this->layout)) {
            $layout = [
                'type' => $this->layoutType(),
                'source' => $this->layoutSource(),
                'header' => $this->layoutHeader(),
                'footer' => $this->layoutFooter(),
            ];
        }

        $result = [
            'sections' => $this->sections,
            'order' => $this->order,
            'layout' => $layout,
            'title' => $this->title,
            'meta' => $this->metaObject->toArray(),
        ];

        if ($this->wrapper !== null) {
            $result['wrapper'] = $this->wrapper;
        }

        return $result;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Magic getter for Blade compatibility (e.g. $page->title, $page->meta, $page->sections).
     */
    public function __get(string $name): mixed
    {
        if (method_exists($this, $name)) {
            return $this->$name();
        }

        return null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! is_string($offset)) {
            return null;
        }

        $array = $this->toArray();

        return $array[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Readonly DTO
    }

    public function offsetUnset(mixed $offset): void
    {
        // Readonly DTO
    }
}
