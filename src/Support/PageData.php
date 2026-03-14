<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

final class PageData implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * @param  array<string, array>  $sections
     * @param  array<string>  $order
     * @param  array<string, mixed>  $layout
     */
    public function __construct(
        private readonly array $sections = [],
        private readonly array $order = [],
        private readonly array $layout = [],
        private readonly string $title = '',
        private readonly array $meta = [],
    ) {}

    /**
     * Create a PageData instance from a raw decoded page JSON array.
     *
     * When the JSON has no `layout` key (or an empty one), the caller may
     * supply a `$defaultLayout` array (built by LayoutParser) which will be
     * used as the base. Any keys already present in `$data['layout']` will
     * deep-merge on top, so per-page settings always win.
     *
     * Layout shape:
     *
     *   layout: {
     *     type: "page",
     *     header: { sections: { "header": {...} }, order: ["header"] },
     *     footer: { sections: { "footer": {...} }, order: ["footer"] }
     *   }
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $defaultLayout  Default layout from LayoutParser (optional)
     */
    public static function fromArray(array $data, array $defaultLayout = []): self
    {
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $order = is_array($data['order'] ?? null) ? $data['order'] : array_keys($sections);

        // Merge stored layout over the default, so per-page settings win.
        $storedLayout = is_array($data['layout'] ?? null) ? $data['layout'] : [];
        $layout = self::mergeLayouts($defaultLayout, $storedLayout);

        return new self(
            sections: $sections,
            order: $order,
            layout: $layout,
            title: (string) ($data['title'] ?? ''),
            meta: is_array($data['meta'] ?? null) ? $data['meta'] : [],
        );
    }

    /**
     * Merge a default layout with a stored (per-page) layout.
     *
     * Layout structure: { type, header: { sections, order }, footer: { sections, order } }
     *
     * Rules:
     *  - `type` from $stored wins if present.
     *  - Each zone (header/footer) is merged independently: stored section
     *    settings deep-merge over default section settings, so a partially-saved
     *    header still gets the schema defaults for any missing keys.
     *  - Zone `order` from $stored wins if non-empty; otherwise default order used.
     *
     * @param  array<string, mixed>  $default
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function mergeLayouts(array $default, array $stored): array
    {
        if (empty($default) && empty($stored)) {
            return [];
        }

        $type = $stored['type'] ?? $default['type'] ?? 'page';

        return [
            'type' => $type,
            'header' => self::mergeZone(
                $default['header'] ?? [],
                $stored['header'] ?? [],
            ),
            'footer' => self::mergeZone(
                $default['footer'] ?? [],
                $stored['footer'] ?? [],
            ),
        ];
    }

    /**
     * Merge a single zone (header or footer) from default and stored data.
     *
     * @param  array<string, mixed>  $default
     * @param  array<string, mixed>  $stored
     * @return array{sections: array, order: array}
     */
    private static function mergeZone(array $default, array $stored): array
    {
        $defaultSections = $default['sections'] ?? [];
        $storedSections = $stored['sections'] ?? [];

        // Start with defaults, deep-merge stored settings on top.
        $sections = $defaultSections;

        foreach ($storedSections as $key => $storedSection) {
            if (isset($sections[$key])) {
                $sections[$key] = array_merge($sections[$key], $storedSection, [
                    'settings' => array_merge(
                        $sections[$key]['settings'] ?? [],
                        $storedSection['settings'] ?? [],
                    ),
                    'blocks' => $storedSection['blocks'] ?? $sections[$key]['blocks'] ?? [],
                    'order' => $storedSection['order'] ?? $sections[$key]['order'] ?? [],
                ]);
            } else {
                $sections[$key] = $storedSection;
            }
        }

        // Order: stored wins if non-empty, otherwise default, otherwise key order.
        $storedOrder = isset($stored['order']) && is_array($stored['order']) && ! empty($stored['order'])
            ? $stored['order']
            : null;
        $order = $storedOrder ?? ($default['order'] ?? array_keys($sections));

        return [
            'sections' => $sections,
            'order' => $order,
        ];
    }

    /**
     * Return the ordered list of section IDs defining render order.
     *
     * @return array<string>
     */
    public function order(): array
    {
        return $this->order;
    }

    /**
     * Return the raw section data for a given ID, or null if not found.
     */
    public function section(string $id): ?array
    {
        return $this->sections[$id] ?? null;
    }

    /**
     * Return all sections as a raw map keyed by section ID.
     *
     * @return array<string, array>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * Return the page title, falling back to an empty string when not set.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Return the page SEO metadata.
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Return the layout type identifier (defaults to "page").
     */
    public function layoutType(): string
    {
        return (string) ($this->layout['type'] ?? 'page');
    }

    /**
     * Return the raw section data for a given layout key, or null if absent/disabled.
     *
     * Searches both the header and footer zones, returning the first match.
     * Normalises missing `blocks` and `order` to empty arrays before returning.
     */
    public function layoutSection(string $key): ?array
    {
        // Search header zone then footer zone.
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
     * Return all layout sections as a normalised flat map keyed by section key.
     *
     * Merges header and footer zones so callers that just want the full list
     * don't need to know about zones.
     *
     * @return array<string, array>
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
     * Return the header zone array (sections + order), or an empty zone.
     *
     * @return array{sections: array, order: array}
     */
    public function layoutHeader(): array
    {
        $zone = $this->layout['header'] ?? [];

        return [
            'sections' => array_map(fn (array $s) => array_merge(['blocks' => [], 'order' => []], $s), $zone['sections'] ?? []),
            'order' => $zone['order'] ?? [],
        ];
    }

    /**
     * Return the footer zone array (sections + order), or an empty zone.
     *
     * @return array{sections: array, order: array}
     */
    public function layoutFooter(): array
    {
        $zone = $this->layout['footer'] ?? [];

        return [
            'sections' => array_map(fn (array $s) => array_merge(['blocks' => [], 'order' => []], $s), $zone['sections'] ?? []),
            'order' => $zone['order'] ?? [],
        ];
    }

    /**
     * Determine if the page has no sections.
     */
    public function isEmpty(): bool
    {
        return $this->sections === [];
    }

    /**
     * Determine if the page has at least one section.
     */
    public function isNotEmpty(): bool
    {
        return $this->sections !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $layout = [];

        if (! empty($this->layout)) {
            $layout = [
                'type' => $this->layoutType(),
                'header' => $this->layoutHeader(),
                'footer' => $this->layoutFooter(),
            ];
        }

        return [
            'sections' => $this->sections,
            'order' => $this->order,
            'layout' => $layout,
            'title' => $this->title,
            'meta' => $this->meta,
        ];
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
     * Magic getter for Blade compatibility (e.g. $page->title).
     */
    public function __get(string $name): mixed
    {
        if (method_exists($this, $name)) {
            return $this->$name();
        }

        return null;
    }
}
