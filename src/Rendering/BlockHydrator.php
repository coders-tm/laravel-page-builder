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

namespace PageBuilder\Rendering;

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Settings;
use PageBuilder\Registry\BlockRegistry;
use PageBuilder\Schema\BlockSchema;
use PageBuilder\Schema\SectionSchema;

/**
 * Hydrates raw block data arrays into typed Block / BlockCollection objects.
 *
 * Extracted from Renderer to follow the Single Responsibility Principle:
 * Renderer handles rendering; BlockHydrator handles data hydration.
 */
class BlockHydrator
{
    /**
     * Cache for schema settingDefaults() results keyed by block type.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $settingDefaultsCache = [];

    /**
     * Cache for hydrated block schemas keyed by block type.
     *
     * @var array<string, BlockSchema|null>
     */
    protected array $blockSchemaCache = [];

    public function __construct(
        protected readonly BlockRegistry $blockRegistry,
    ) {}

    /**
     * Hydrate a raw block array into a typed Block object.
     *
     * @param  array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}  $data
     */
    public function hydrateBlock(string $blockId, array $data, bool $editor = false): Block
    {
        $blockType = $data['type'] ?? Block::DEFAULT_TYPE;

        $themeEntry = $this->blockRegistry->get($blockType);
        $blockSchema = $themeEntry?->schema;

        $settingDefaults = $blockSchema instanceof BlockSchema
            ? $this->getCachedSettingDefaults($blockType, $blockSchema)
            : [];

        $nestedBlocks = $this->hydrateBlocks(
            rawBlocks: is_array($data['blocks'] ?? null) ? $data['blocks'] : [],
            blockOrder: $data['order'] ?? null,
            schema: null,
            editor: $editor,
        );

        return new Block([
            'id' => $blockId,
            'type' => $blockType,
            'name' => $blockSchema?->name,
            'disabled' => ! empty($data['disabled']),
            'settings' => new Settings(
                values: $data['settings'] ?? [],
                defaults: $settingDefaults,
            ),
            'blocks' => $nestedBlocks,
        ]);
    }

    /**
     * Build an ordered BlockCollection from raw block data.
     *
     * Disabled blocks are always skipped.
     *
     * Schema resolution: inline section block → BlockRegistry fallback.
     * Nested blocks are hydrated recursively.
     *
     * @param  array<string, array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}>  $rawBlocks
     * @param  list<string>|null  $blockOrder
     */
    public function hydrateBlocks(
        array $rawBlocks,
        ?array $blockOrder,
        ?SectionSchema $schema,
        bool $editor,
    ): BlockCollection {
        $order = $blockOrder ?? array_keys($rawBlocks);
        $ordered = [];

        foreach ($order as $blockId) {
            if (! isset($rawBlocks[$blockId])) {
                continue;
            }

            $raw = $rawBlocks[$blockId];
            $disabled = ! empty($raw['disabled']);

            if ($disabled && ! $editor) {
                continue;
            }

            $blockType = $raw['type'] ?? Block::DEFAULT_TYPE;

            // 1. Look for an inline (static) block schema in the section.
            $blockSchema = $schema?->blockSchema($blockType);

            // 2. Fall back to BlockRegistry for type-reference-only entries,
            //    @theme wildcards, and recursive nested hydration (schema === null).
            if ($blockSchema === null) {
                $blockSchema = $this->getCachedBlockSchema($blockType);
            }

            $settingDefaults = $blockSchema instanceof BlockSchema
                ? $this->getCachedSettingDefaults($blockType, $blockSchema)
                : [];

            // Recursively hydrate nested blocks (e.g. columns inside a row).
            $nestedBlocks = $this->hydrateBlocks(
                rawBlocks: is_array($raw['blocks'] ?? null) ? $raw['blocks'] : [],
                blockOrder: $raw['order'] ?? null,
                schema: null,
                editor: $editor,
            );

            $block = new Block([
                'id' => $blockId,
                'type' => $blockType,
                'name' => $blockSchema?->name,
                'disabled' => $disabled,
                'settings' => new Settings(
                    values: $raw['settings'] ?? [],
                    defaults: $settingDefaults,
                ),
                'blocks' => $nestedBlocks,
            ]);

            // Post-hydration: Connect child blocks to parent block
            foreach ($nestedBlocks as $child) {
                $child->parent = $block;
            }

            $ordered[$blockId] = $block;
        }

        return new BlockCollection($ordered);
    }

    /**
     * Get a block schema from the registry, with caching.
     */
    protected function getCachedBlockSchema(string $blockType): ?BlockSchema
    {
        if (array_key_exists($blockType, $this->blockSchemaCache)) {
            return $this->blockSchemaCache[$blockType];
        }

        $themeEntry = $this->blockRegistry->get($blockType);
        $this->blockSchemaCache[$blockType] = $themeEntry?->schema;

        return $this->blockSchemaCache[$blockType];
    }

    /**
     * Get setting defaults for a block type, with caching.
     *
     * @return array<string, mixed>
     */
    protected function getCachedSettingDefaults(string $blockType, BlockSchema $schema): array
    {
        return $this->settingDefaultsCache[$blockType] ??= $schema->settingDefaults();
    }
}
