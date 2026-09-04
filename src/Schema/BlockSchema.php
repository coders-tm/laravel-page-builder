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

namespace PageBuilder\Schema;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Immutable schema definition for a block type.
 *
 * Both `name` and `type` are required — no silent fallbacks.
 * Registered globally via BlockRegistry, or declared inline inside a SectionSchema.
 */
class BlockSchema implements Arrayable, Jsonable, JsonSerializable
{
    use HasSettingDefaults;

    public const THEME_WILDCARD = '@theme';

    public readonly string $type;

    public readonly string $name;

    /** Maximum number of this block type allowed in a single parent (0 = unlimited). */
    public readonly int $limit;

    /** @var array<int, SettingSchema> */
    public readonly array $settings;

    /**
     * Child block type references (`['type' => 'column']`, `['type' => '@theme']`).
     *
     * Unlike sections, blocks cannot declare inline child schemas — only references.
     *
     * @var array<int, array{type: string}>
     */
    public readonly array $blocks;

    /** @var array<int, array<string, mixed>> */
    public readonly array $presets;

    /**
     * @throws \InvalidArgumentException When required `name` or `type` is missing.
     */
    public function __construct(array $schema)
    {
        if (! isset($schema['name']) || $schema['name'] === '') {
            throw new \InvalidArgumentException(
                "Block schema is missing required 'name' attribute."
                    .(isset($schema['type']) ? " (type: {$schema['type']})" : '')
            );
        }

        if (! isset($schema['type']) || $schema['type'] === '') {
            throw new \InvalidArgumentException(
                "Block schema is missing required 'type' attribute."
                    ." (name: {$schema['name']})"
            );
        }

        $this->type = $schema['type'];
        $this->name = $schema['name'];
        $this->limit = (int) ($schema['limit'] ?? 0);

        $this->settings = array_map(
            fn (array $s) => new SettingSchema($s),
            $schema['settings'] ?? [],
        );

        $this->blocks = $schema['blocks'] ?? [];
        $this->presets = $schema['presets'] ?? [];
    }

    /**
     * Whether this block accepts any block from the global BlockRegistry as a child.
     */
    public function acceptsThemeBlocks(): bool
    {
        foreach ($this->blocks as $ref) {
            if (($ref['type'] ?? null) === self::THEME_WILDCARD) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function settingDefaults(): array
    {
        return self::extractSettingDefaults($this->settings);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'name' => $this->name,
            'limit' => $this->limit,
            'settings' => array_map(fn (SettingSchema $s) => $s->toArray(), $this->settings),
        ];

        if (! empty($this->blocks)) {
            $data['blocks'] = $this->blocks;
        }

        if (! empty($this->presets)) {
            $data['presets'] = $this->presets;
        }

        return $data;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
