<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Rendering;

use Coderstm\PageBuilder\Collections\BlockCollection;
use Coderstm\PageBuilder\Components\BaseComponent;
use Coderstm\PageBuilder\Components\Block;
use Coderstm\PageBuilder\Components\Section;
use Coderstm\PageBuilder\Components\Settings;
use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Registry\BlockRegistry;
use Coderstm\PageBuilder\Registry\SectionRegistry;
use Coderstm\PageBuilder\Schema\BlockSchema;
use Coderstm\PageBuilder\Schema\SectionSchema;
use Illuminate\Support\Facades\View;

/**
 * Renders sections and blocks into HTML strings.
 *
 * Hydrates raw JSON data into typed runtime objects (Section, Block)
 * using schema definitions from the SectionRegistry, then renders
 * them through Blade views.
 */
class Renderer
{
    public function __construct(
        protected readonly SectionRegistry $registry,
        protected readonly BlockRegistry $blockRegistry,
    ) {}

    /**
     * Render a Section object into HTML.
     */
    public function renderSection(Section $section): string
    {
        $meta = $this->registry->get($section->type);

        if ($meta === null) {
            return "<!-- Section type '{$section->type}' not found -->";
        }

        $viewName = $meta['view'];

        if (! View::exists($viewName)) {
            return "<!-- View '{$viewName}' not found -->";
        }

        $html = (string) view($viewName, ['section' => $section])->render();

        if (PageBuilder::editor()) {
            $html = EditorAttributes::autoInjectLiveText($html, $section);
        }

        return $html;
    }

    /**
     * Render a single Block into HTML.
     * The parent (Section or Block) is passed so views can access context.
     */
    public function renderBlock(Block $block, ?BaseComponent $parent = null): string
    {
        $viewName = "blocks.{$block->type}";

        if (! View::exists($viewName)) {
            return "<!-- Block view '{$viewName}' not found -->";
        }

        return (string) view($viewName, [
            'block' => $block,
            'parent' => $parent ?? $block->parent,
        ])->render();
    }

    /**
     * Render all blocks within a section, concatenating the HTML.
     */
    public function renderBlocks(Section $section): string
    {
        $html = '';

        foreach ($section->blocks as $block) {
            $html .= $this->renderBlock($block, $section);
        }

        return $html;
    }

    /**
     * Render all child blocks within a Block (e.g. columns inside a row).
     */
    public function renderBlockChildren(Block $block): string
    {
        $html = '';

        foreach ($block->blocks as $child) {
            $html .= $this->renderBlock($child, $block);
        }

        return $html;
    }

    /**
     * Hydrate a raw block array into a typed Block object.
     */
    public function hydrateBlock(string $blockId, array $data, bool $editor = false): Block
    {
        $blockType = $data['type'] ?? 'block';

        $themeEntry = $this->blockRegistry->get($blockType);
        $blockSchema = $themeEntry['schema'] ?? null;

        $settingDefaults = $blockSchema instanceof BlockSchema
            ? $blockSchema->settingDefaults()
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
     * Hydrate a raw section array (from page JSON) into a typed Section object.
     */
    public function hydrateSection(string $sectionId, array $data, bool $editor = false): Section
    {
        $type = $data['type'] ?? '';
        $meta = $this->registry->get($type);

        /** @var SectionSchema|null $schema */
        $schema = $meta['schema'] ?? null;

        $settingDefaults = $schema ? $schema->settingDefaults() : [];

        $section = new Section([
            'id' => $sectionId,
            'type' => $type,
            'name' => $schema?->name,
            'disabled' => $data['disabled'] ?? false,
            'settings' => new Settings(
                values: $data['settings'] ?? [],
                defaults: $settingDefaults,
            ),
            'blocks' => $this->hydrateBlocks(
                rawBlocks: is_array($data['blocks'] ?? null) ? $data['blocks'] : [],
                blockOrder: $data['order'] ?? null,
                schema: $schema,
                editor: $editor,
            ),
        ]);

        // Post-hydration: Connect blocks to section parent
        foreach ($section->blocks as $block) {
            $block->parent = $section;
        }

        return $section;
    }

    /**
     * Build an ordered BlockCollection from raw block data.
     *
     * Disabled blocks are always skipped.
     *
     * Schema resolution: inline section block → BlockRegistry fallback.
     * Nested blocks are hydrated recursively.
     */
    protected function hydrateBlocks(
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

            if ($disabled) {
                continue;
            }

            $blockType = $raw['type'] ?? 'block';

            // 1. Look for an inline (static) block schema in the section.
            $blockSchema = $schema?->blockSchema($blockType);

            // 2. Fall back to BlockRegistry for type-reference-only entries,
            //    @theme wildcards, and recursive nested hydration (schema === null).
            if ($blockSchema === null) {
                $themeEntry = $this->blockRegistry->get($blockType);
                $blockSchema = $themeEntry['schema'] ?? null;
            }

            $settingDefaults = $blockSchema instanceof BlockSchema
                ? $blockSchema->settingDefaults()
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
     * Render a raw section array directly (for API preview calls).
     *
     * Hydrates the data into a Section object, then renders it.
     */
    public function renderRawSection(string $sectionId, array $sectionData, bool $editor = false): string
    {
        if ($editor) {
            PageBuilder::enableEditor();
        }

        try {
            $section = $this->hydrateSection($sectionId, $sectionData, $editor);

            return $this->renderSection($section);
        } finally {
            if ($editor) {
                PageBuilder::disableEditor();
            }
        }
    }

    /**
     * Render a raw block array directly (for API preview calls).
     *
     * Hydrates the data into a Block object, then renders it.
     */
    public function renderRawBlock(string $blockId, array $blockData, bool $editor = false): string
    {
        if ($editor) {
            PageBuilder::enableEditor();
        }

        try {
            $block = $this->hydrateBlock($blockId, $blockData, $editor);

            return $this->renderBlock($block);
        } finally {
            if ($editor) {
                PageBuilder::disableEditor();
            }
        }
    }
}
