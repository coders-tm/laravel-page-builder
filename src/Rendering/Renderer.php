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
use Illuminate\Support\Facades\Blade;
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
    /**
     * Holds extra view variables (e.g. 'page') for the section currently being
     * rendered, so that nested block renders can access them for Blade evaluation.
     *
     * @var array<string, mixed>
     */
    protected array $renderContext = [];

    public function __construct(
        protected readonly SectionRegistry $registry,
        protected readonly BlockRegistry $blockRegistry,
    ) {}

    /**
     * Render a Section object into HTML.
     *
     * @param  array<string, mixed>  $data  Extra view variables (e.g. ['page' => $pageData])
     */
    public function renderSection(Section $section, array $data = []): string
    {
        $meta = $this->registry->get($section->type);

        if ($meta === null) {
            return "<!-- Section type '{$section->type}' not found -->";
        }

        $viewName = $meta['view'];

        if (! View::exists($viewName)) {
            return "<!-- View '{$viewName}' not found -->";
        }

        // Alias __pb_page to page for evaluation so that {{ $page->title }} works
        // in settings, but we don't pass it to the view to avoid overwriting
        // the shared database page model.
        $evalData = $data;
        if (! isset($evalData['page']) && isset($evalData['__pb_page'])) {
            $evalData['page'] = $evalData['__pb_page'];
        }

        if (! empty($evalData)) {
            $section = $this->evaluateBladeInComponentSettings($section, $evalData);
        }

        // Store context so nested @blocks() renders can pick it up.
        $previous = $this->renderContext;
        $this->renderContext = $evalData;

        try {
            $html = (string) view($viewName, array_merge($data, ['section' => $section]))->render();
        } finally {
            $this->renderContext = $previous;
        }

        if (PageBuilder::editor()) {
            $html = EditorAttributes::autoInjectLiveText($html, $section);
        }

        return $html;
    }

    /**
     * Evaluate Blade syntax in string setting values, returning a new instance
     * of the component with re-evaluated settings so that expressions like
     * {{ $page->title }} are resolved before the view escapes them.
     *
     * Only processes strings that actually contain {{ or @, so there is no
     * overhead for plain-text settings.
     *
     * @template T of BaseComponent
     *
     * @param  T  $component
     * @param  array<string, mixed>  $data  Variables available to Blade expressions.
     * @return T
     */
    protected function evaluateBladeInComponentSettings(BaseComponent $component, array $data): BaseComponent
    {
        $raw = $component->settings->raw();
        $defaults = $component->settings->defaults();
        $changed = false;

        foreach ($raw as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            if (! str_contains($value, '{{') && ! str_contains($value, '@')) {
                continue;
            }

            try {
                $raw[$key] = Blade::render($value, $data, deleteCachedView: true);
                $changed = true;
            } catch (\Throwable) {
                // Leave unchanged on any compile/runtime error
            }
        }

        if (! $changed) {
            return $component;
        }

        return $component->withSettings(new Settings($raw, $defaults));
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

        // Evaluate Blade syntax in block settings using the active render context
        // (populated by renderSection when $data contains variables like 'page').
        if (! empty($this->renderContext)) {
            $block = $this->evaluateBladeInComponentSettings($block, $this->renderContext);
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
     *
     * @param  array<string, mixed>  $data  Extra view variables (e.g. ['page' => $pageData])
     */
    public function renderRawSection(string $sectionId, array $sectionData, bool $editor = false, array $data = []): string
    {
        if ($editor) {
            PageBuilder::enableEditor();
        }

        try {
            $section = $this->hydrateSection($sectionId, $sectionData, $editor);

            return $this->renderSection($section, $data);
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
