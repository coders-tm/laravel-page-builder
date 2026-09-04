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

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use PageBuilder\Components\BaseComponent;
use PageBuilder\Components\Block;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\Contracts\RendererInterface;
use PageBuilder\PageBuilder;
use PageBuilder\Registry\SectionRegistry;
use PageBuilder\Schema\SectionSchema;

/**
 * Renders sections and blocks into HTML strings.
 *
 * Hydrates raw JSON data into typed runtime objects (Section, Block)
 * using schema definitions from the SectionRegistry, then renders
 * them through Blade views.
 */
class Renderer implements RendererInterface
{
    /**
     * Holds extra view variables (e.g. 'page') for the section currently being
     * rendered, so that nested block renders can access them for Blade evaluation.
     *
     * @var array<string, mixed>
     */
    protected array $renderContext = [];

    /**
     * Cache for View::exists() results to avoid repeated filesystem checks.
     *
     * @var array<string, bool>
     */
    protected array $viewExistsCache = [];

    /**
     * Cache for View objects keyed by view name to avoid repeated factory calls.
     *
     * @var array<string, \Illuminate\View\View>
     */
    protected array $viewCache = [];

    public function __construct(
        protected readonly SectionRegistry $registry,
        protected readonly BlockHydrator $hydrator,
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

        $viewName = $meta->view;

        if (! $this->viewExists($viewName)) {
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
            $viewData = array_merge($data, ['section' => $section]);
            $html = $this->renderViewCached($viewName, $viewData);
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
     * @param  array<string, mixed>  $data  Variables available to Blade expressions.
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
                $raw[$key] = Blade::render($value, $data);
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

        if (! $this->viewExists($viewName)) {
            return "<!-- Block view '{$viewName}' not found -->";
        }

        // Evaluate Blade syntax in block settings using the active render context
        // (populated by renderSection when $data contains variables like 'page').
        if (! empty($this->renderContext)) {
            $block = $this->evaluateBladeInComponentSettings($block, $this->renderContext);
        }

        return $this->renderViewCached($viewName, [
            'block' => $block,
            'parent' => $parent ?? $block->parent,
        ]);
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
     * Hydrate a raw section array (from page JSON) into a typed Section object.
     *
     * @param  array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}  $data
     */
    public function hydrateSection(string $sectionId, array $data, bool $editor = false): Section
    {
        $type = $data['type'] ?? '';
        $meta = $this->registry->get($type);

        /** @var SectionSchema|null $schema */
        $schema = $meta?->schema;

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
            'blocks' => $this->hydrator->hydrateBlocks(
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
     * Render a raw section array directly (for API preview calls).
     *
     * Hydrates the data into a Section object, then renders it.
     *
     * @param  array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}  $sectionData
     * @param  array<string, mixed>  $data  Extra view variables (e.g. ['page' => $pageData])
     */
    public function renderRawSection(string $sectionId, array $sectionData, bool $editor = false, array $data = []): string
    {
        return $this->withEditor($editor, fn () => $this->renderSection(
            $this->hydrateSection($sectionId, $sectionData, $editor), $data
        ));
    }

    /**
     * Render a raw block array directly (for API preview calls).
     *
     * Hydrates the data into a Block object, then renders it.
     *
     * @param  array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}  $blockData
     */
    public function renderRawBlock(string $blockId, array $blockData, bool $editor = false): string
    {
        return $this->withEditor($editor, fn () => $this->renderBlock(
            $this->hydrator->hydrateBlock($blockId, $blockData, $editor)
        ));
    }

    /**
     * Execute a render callback with editor mode optionally enabled.
     *
     * Ensures editor mode is always disabled after the callback completes,
     * even if the callback throws an exception.
     */
    private function withEditor(bool $editor, callable $render): string
    {
        if ($editor) {
            PageBuilder::enableEditor();
        }

        try {
            return $render();
        } finally {
            if ($editor) {
                PageBuilder::disableEditor();
            }
        }
    }

    /**
     * Check if a view exists, with filesystem result caching.
     */
    protected function viewExists(string $viewName): bool
    {
        return $this->viewExistsCache[$viewName] ??= View::exists($viewName);
    }

    /**
     * Render a view using a cached View object to avoid repeated factory calls.
     *
     * @param  array<string, mixed>  $data
     */
    protected function renderViewCached(string $viewName, array $data): string
    {
        if (! isset($this->viewCache[$viewName])) {
            $this->viewCache[$viewName] = view($viewName);
        }

        $view = clone $this->viewCache[$viewName];
        $view->with($data);

        return (string) $view->render();
    }
}
