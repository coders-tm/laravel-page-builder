<?php

namespace Coderstm\PageBuilder\Http\Controllers;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Registry\BlockRegistry;
use Coderstm\PageBuilder\Registry\LayoutParser;
use Coderstm\PageBuilder\Registry\SectionRegistry;
use Coderstm\PageBuilder\Rendering\Renderer;
use Coderstm\PageBuilder\Services\PageRegistry;
use Coderstm\PageBuilder\Services\PageRenderer;
use Coderstm\PageBuilder\Services\PageStorage;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Coderstm\PageBuilder\Support\PageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageBuilderController extends Controller
{
    public function __construct(
        protected PageRenderer $pageRenderer,
        protected PageStorage $pageStorage,
        protected SectionRegistry $sectionRegistry,
        protected BlockRegistry $blockRegistry,
        protected PageRegistry $pageRegistry,
        protected Renderer $renderer,
        protected ThemeSettings $themeSettings,
        protected LayoutParser $layoutParser,
    ) {}

    /**
     * GET /pagebuilder/{slug?}
     *
     * Render the PageBuilder editor React application.
     */
    public function editor(?string $slug = null): View
    {
        return view('pagebuilder::layout', [
            'config' => PageBuilder::scriptVariables(),
        ]);
    }

    /**
     * GET /pagebuilder/pages
     *
     * List all available pages from the canonical page registry.
     */
    public function pages(): JsonResponse
    {
        return response()->json($this->pageRegistry->pages());
    }

    /**
     * GET /pagebuilder/page/{slug}
     *
     * Get a specific page JSON data.
     * Returns an empty page structure when no JSON file exists,
     * allowing the editor to create new pages from scratch.
     *
     * The response always includes a `layout` key with `type`, `sections`,
     * and `order` — populated from the active layout Blade file when the
     * page JSON has no `layout` stored yet.
     */
    public function page(string $slug = 'home'): JsonResponse
    {
        $stored = $this->pageStorage->load($slug);
        $layoutType = $stored?->layoutType() ?? 'page';
        $defaultLayout = $this->layoutParser->defaultLayout($layoutType);

        $page = $stored !== null
            ? PageData::fromArray($stored->toArray(), $defaultLayout)
            : PageData::fromArray([], $defaultLayout);

        $data = $page->toArray();

        // Merge database meta into the response so the editor has it.
        $dbPage = Page::findBySlug($slug);

        if ($dbPage) {
            $data['title'] = $dbPage->title;
            $data['meta'] = array_filter([
                'meta_title' => $dbPage->meta_title,
                'meta_description' => $dbPage->meta_description,
                'meta_keywords' => $dbPage->meta_keywords,
            ]);
        }

        return response()->json($data);
    }

    /**
     * POST /pagebuilder/render-section
     *
     * Render a single section with provided settings.
     * Used by the editor for live preview updates.
     */
    public function renderSection(Request $request): JsonResponse
    {
        $request->validate([
            'section_id' => 'required|string',
            'section_type' => 'required|string',
            'settings' => 'nullable|array',
            'blocks' => 'nullable|array',
            'order' => 'nullable|array',
        ]);

        $sectionId = $request->input('section_id');
        $sectionData = [
            'type' => $request->input('section_type'),
            'settings' => $request->input('settings', []),
            'blocks' => $request->input('blocks', []),
            'order' => $request->input('order', []),
        ];

        $html = $this->renderer->renderRawSection($sectionId, $sectionData, editor: true);

        return response()->json([
            'html' => $html,
        ]);
    }

    /**
     * POST /pagebuilder/render-block
     *
     * Render a single block with provided settings.
     * Used by the editor for block previews in modals.
     */
    public function renderBlock(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'settings' => 'nullable|array',
            'blocks' => 'nullable|array',
            'order' => 'nullable|array',
        ]);

        $blockData = [
            'type' => $request->input('type'),
            'settings' => $request->input('settings', []),
            'blocks' => $request->input('blocks', []),
            'order' => $request->input('order', []),
        ];

        $html = $this->renderer->renderRawBlock('preview_block', $blockData, editor: true);

        return response()->json([
            'html' => $html,
        ]);
    }

    /**
     * POST /pagebuilder/save-page
     *
     * Save page JSON data and persist page meta to the database.
     */
    public function savePage(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string|regex:#^[a-zA-Z0-9\-_\/]+$#',
            'data' => 'required|array',
            'meta' => 'nullable|array',
            'meta.title' => 'nullable|string|max:255',
            'meta.meta_title' => 'nullable|string|max:255',
            'meta.meta_description' => 'nullable|string|max:500',
            'meta.meta_keywords' => 'nullable|string|max:255',
            'theme_settings' => 'nullable|array',
        ]);

        $slug = $request->input('slug');
        $data = array_merge($request->input('data'), [
            'title' => $request->input('meta.title'),
            'meta' => $request->input('meta'),
        ]);

        $saved = $this->pageStorage->save($slug, $data);

        if (! $saved) {
            return response()->json([
                'message' => 'Failed to save page.',
            ], 500);
        }

        // Persist page meta to the database
        $meta = $request->input('meta', []);

        if (! empty($meta)) {
            Page::saveMeta($slug, array_filter([
                'title' => $meta['title'] ?? null,
                'meta_title' => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'meta_keywords' => $meta['meta_keywords'] ?? null,
            ], fn ($v) => $v !== null));
        }

        // Save theme settings when included in the same request
        if ($request->has('theme_settings')) {
            $this->themeSettings->save($request->input('theme_settings', []));
        }

        return response()->json([
            'message' => __('Page has been saved successfully'),
        ]);
    }

    /**
     * GET /pagebuilder/theme-settings
     *
     * Return the theme settings schema and current values.
     */
    public function themeSettings(): JsonResponse
    {
        return response()->json($this->themeSettings->toArray());
    }

    /**
     * POST /pagebuilder/theme-settings
     *
     * Save theme settings values.
     */
    public function saveThemeSettings(Request $request): JsonResponse
    {
        $request->validate([
            'values' => 'required|array',
        ]);

        $saved = $this->themeSettings->save($request->input('values'));

        if (! $saved) {
            return response()->json([
                'message' => 'Failed to save theme settings.',
            ], 500);
        }

        return response()->json([
            'message' => __('Theme settings have been saved successfully'),
        ]);
    }
}
