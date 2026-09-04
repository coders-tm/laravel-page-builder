<?php declare(strict_types=1);

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
use JsonSerializable;

/**
 * Immutable schema definition for a section type.
 *
 * Registered via SectionRegistry. Never holds runtime page data.
 */
class SectionSchema implements Arrayable, JsonSerializable
{
    public readonly string $name;

    public readonly string $tag;

    public readonly string $class;

    public readonly int $limit;

    public readonly int $maxBlocks;

    /** @var array<int, SettingSchema> */
    public readonly array $settings;

    /**
     * Inline block schema definitions declared in this section's @schema blocks array.
     *
     * Only entries with both `type` and `name` are parsed into BlockSchema objects.
     * Type-reference-only entries (`['type' => 'row']`, `['type' => '@theme']`)
     * are stored in {@see $allowedBlockTypes} instead.
     *
     * @var array<int, BlockSchema>
     */
    public readonly array $blocks;

    /**
     * Raw type-reference entries from the schema's blocks array.
     *
     * Each entry is `['type' => '<type>']`. Used to check which block types
     * (or the `@theme` wildcard) this section permits.
     *
     * @var array<int, array{type: string}>
     */
    public readonly array $allowedBlockTypes;

    /** @var array<int, array> */
    public readonly array $presets;

    /**
     * @throws \InvalidArgumentException When the required `name` attribute is missing.
     */
    public function __construct(array $schema)
    {
        if (! isset($schema['name']) || $schema['name'] === '') {
            throw new \InvalidArgumentException(
                "Section schema is missing required 'name' attribute."
            );
        }

        $this->name = $schema['name'];
        $this->tag = $schema['tag'] ?? 'section';
        $this->class = $schema['class'] ?? '';
        $this->limit = (int) ($schema['limit'] ?? 0);
        $this->maxBlocks = (int) ($schema['max_blocks'] ?? 0);

        $this->settings = array_map(
            fn (array $s) => new SettingSchema($s),
            $schema['settings'] ?? [],
        );

        // Separate inline block definitions (have 'name') from type-reference-only entries.
        $inlineBlocks = [];
        $allowedBlockTypes = [];

        foreach ($schema['blocks'] ?? [] as $b) {
            $allowedBlockTypes[] = ['type' => $b['type'] ?? ''];

            if (isset($b['name']) && $b['name'] !== '') {
                // Full inline block schema — requires both type and name.
                $inlineBlocks[] = new BlockSchema($b);
            }
            // Otherwise it's a type-reference-only entry; tracked in allowedBlockTypes above.
        }

        $this->blocks = $inlineBlocks;
        $this->allowedBlockTypes = $allowedBlockTypes;

        $this->presets = $schema['presets'] ?? [];
    }

    /**
     * Find an inline block schema by type.
     *
     * Returns null for type-reference-only entries — callers should fall
     * back to BlockRegistry in that case.
     */
    public function blockSchema(string $type): ?BlockSchema
    {
        foreach ($this->blocks as $block) {
            if ($block->type === $type) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Whether this section accepts any block registered in the global BlockRegistry.
     */
    public function acceptsThemeBlocks(): bool
    {
        foreach ($this->allowedBlockTypes as $ref) {
            if (($ref['type'] ?? null) === '@theme') {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function settingDefaults(): array
    {
        $defaults = [];

        foreach ($this->settings as $setting) {
            if ($setting->id !== null && $setting->id !== '') {
                $defaults[$setting->id] = $setting->default;
            }
        }

        return $defaults;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        // Reconstruct the blocks array in original declaration order.
        $inlineByType = [];
        foreach ($this->blocks as $block) {
            $inlineByType[$block->type] = $block;
        }

        $blocks = array_map(function (array $ref) use ($inlineByType): array {
            $type = $ref['type'] ?? '';

            return isset($inlineByType[$type])
                ? $inlineByType[$type]->toArray()
                : ['type' => $type];
        }, $this->allowedBlockTypes);

        return [
            'name' => $this->name,
            'tag' => $this->tag,
            'class' => $this->class,
            'limit' => $this->limit,
            'max_blocks' => $this->maxBlocks,
            'settings' => array_map(fn (SettingSchema $s) => $s->toArray(), $this->settings),
            'blocks' => $blocks,
            'presets' => $this->presets,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
