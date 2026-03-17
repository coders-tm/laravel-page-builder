<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Services;

use Coderstm\PageBuilder\Http\Controllers\WebPageController;
use Coderstm\PageBuilder\PageBuilder;
use Coderstm\PageBuilder\Registry\LayoutParser;
use Coderstm\PageBuilder\Support\PageData;
use Coderstm\PageBuilder\Support\TemplateVariableResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class PageService
{
    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly PageStorage $pageStorage,
        protected readonly LayoutParser $layoutParser,
        protected readonly EditorPreviewShell $editorPreviewShell,
        protected readonly TemplateStorage $templateStorage,
        protected readonly TemplateVariableResolver $variableResolver,
    ) {}

    /**
     * Resolve and render a page by slug, returning the appropriate HTTP response.
     *
     * Resolution order:
     *   1. Editor mode  — always renders from the stored JSON (Blade views bypassed).
     *   2. Custom Blade view  — view("pages.{slug}") if it exists.
     *   3. Page builder JSON  — sections rendered through PageRenderer.
     *   4. 404.
     *
     * @param  array<string, string|null>  $meta  Optional overrides for title, meta_title,
     *                                            meta_description, meta_keywords.
     */
    public function render(string $slug, array $meta = []): mixed
    {
        if (! preg_match('/^[a-z0-9\-_]+$/i', $slug)) {
            abort(404);
        }

        $dbPage = $this->findBySlug($slug);
        $stored = $this->pageStorage->load($slug);
        $layoutType = $stored?->layoutType() ?? 'page';
        $defaultLayout = $this->layoutParser->defaultLayout($layoutType);

        // Share the DB page model with all views rendered in this request,
        // so section views (e.g. page-content) can access $page->title, $page->content, etc.
        View::share('page', $dbPage);

        // ── 1. Editor mode ────────────────────────────────────────────────
        if (PageBuilder::editor()) {
            $page = $this->buildPage($stored, $defaultLayout, $dbPage);

            return view('pagebuilder::page', [
                ...$this->pageMeta($dbPage, $page, $meta),
                'slug' => $slug,
                '__pb_content' => request()->boolean('pb-preview')
                    ? $this->editorPreviewShell->render()
                    : $this->pageRenderer->renderPage($page, editor: true),
                '__pb_layout' => $page,
            ]);
        }

        // ── 2. Custom Blade view ──────────────────────────────────────────
        if (View::exists("pages.{$slug}")) {
            return view("pages.{$slug}", [
                ...$this->pageMeta($dbPage, $stored, $meta),
                'slug' => $slug,
            ]);
        }

        // ── 3. Page builder JSON ──────────────────────────────────────────
        if ($stored !== null) {
            $page = $this->buildPage($stored, $defaultLayout, $dbPage);

            return view('pagebuilder::page', [
                ...$this->pageMeta($dbPage, $page, $meta),
                'slug' => $slug,
                '__pb_content' => $this->pageRenderer->renderCached($slug, $page),
                '__pb_layout' => $page,
            ]);
        }

        // ── 4. Template fallback ──────────────────────────────────────────
        $templateData = $this->resolveTemplate($dbPage);

        if ($templateData !== null) {
            $resolvedData = $this->variableResolver->resolve($templateData, $dbPage);
            $templateLayout = $this->resolveTemplateLayout($resolvedData);
            $page = $this->buildPageFromTemplate($resolvedData, $templateLayout, $dbPage);

            return view('pagebuilder::page', [
                ...$this->pageMeta($dbPage, $page, $meta),
                'slug' => $slug,
                '__pb_content' => $this->pageRenderer->renderCached($slug, $page),
                '__pb_layout' => $page,
            ]);
        }

        // ── 5. Nothing found ──────────────────────────────────────────────
        abort(404);
    }

    /**
     * Resolve template data for the given DB page.
     *
     * Tries the page's own template first; falls back to the default "page"
     * template. Returns null when neither template file exists.
     *
     * @return array<string, mixed>|null
     */
    private function resolveTemplate(mixed $dbPage): ?array
    {
        $templateName = (string) ($dbPage?->template ?? '');
        $templateName = $templateName !== '' ? $templateName : 'page';

        $data = $this->templateStorage->load($templateName);

        // If a specific template was requested but not found, try the default.
        if ($data === null && $templateName !== 'page') {
            $data = $this->templateStorage->load('page');
        }

        return $data;
    }

    /**
     * Determine the default layout array for a template's layout declaration.
     *
     * @param  array<string, mixed>  $templateData
     * @return array<string, mixed>
     */
    private function resolveTemplateLayout(array $templateData): array
    {
        $layout = $templateData['layout'] ?? 'page';

        // layout: false → render without any layout zones
        if ($layout === false) {
            return [];
        }

        $layoutType = is_string($layout) && $layout !== '' ? $layout : 'page';

        return $this->layoutParser->defaultLayout($layoutType);
    }

    /**
     * Build a PageData instance from template JSON data.
     *
     * @param  array<string, mixed>  $templateData  Resolved (variable-substituted) template data
     * @param  array<string, mixed>  $defaultLayout
     */
    private function buildPageFromTemplate(array $templateData, array $defaultLayout, mixed $dbPage): PageData
    {
        return PageData::fromArray([
            'sections' => $templateData['sections'] ?? [],
            'order' => $templateData['order'] ?? [],
            'wrapper' => $templateData['wrapper'] ?? null,
            'title' => $dbPage?->title ?? '',
        ], $defaultLayout);
    }

    /**
     * Build a PageData instance from stored JSON, merging the DB page title.
     */
    private function buildPage(?PageData $stored, array $defaultLayout, mixed $dbPage): PageData
    {
        $data = $stored?->toArray() ?? [];
        $data['title'] = $dbPage?->title ?? $data['title'] ?? '';

        return PageData::fromArray($data, $defaultLayout);
    }

    /**
     * Extract SEO meta fields, with caller-supplied $meta taking highest precedence.
     *
     * Priority: $meta argument → DB record → stored JSON → null.
     *
     * @param  array<string, string|null>  $meta
     * @return array{title: ?string, meta_title: ?string, meta_description: ?string, meta_keywords: ?string}
     */
    private function pageMeta(mixed $dbPage, ?PageData $stored = null, array $meta = []): array
    {
        $storedMeta = $stored?->meta() ?? [];

        return [
            'title' => $meta['title'] ?? $dbPage?->title ?? $stored?->title(),
            'meta_title' => $meta['meta_title'] ?? $dbPage?->meta_title ?? $storedMeta['meta_title'] ?? null,
            'meta_description' => $meta['meta_description'] ?? $dbPage?->meta_description ?? $storedMeta['meta_description'] ?? null,
            'meta_keywords' => $meta['meta_keywords'] ?? $dbPage?->meta_keywords ?? $storedMeta['meta_keywords'] ?? null,
        ];
    }

    public function routes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        foreach (app(PageRegistry::class)->pages() as $page) {
            $slug = $page['slug'];
            $parent = $page['parent'] ?? null;
            $path = $parent ? "{$parent}/{$slug}" : $slug;

            if ($path) {
                Route::get($path, [WebPageController::class, 'pages'])
                    ->defaults('slug', $slug)
                    ->name('pages.'.$slug);
            }
        }

        Route::get('/', [WebPageController::class, 'pages'])
            ->defaults('slug', 'home')
            ->name('pages.home');
    }

    /**
     * Return all active pages keyed by slug, in the shape expected by PageRegistry.
     */
    public function allActive(): array
    {
        return PageBuilder::$pageModel::where('is_active', true)
            ->get()
            ->keyBy('slug')
            ->map(fn ($page) => [
                'id' => $page->id,
                'slug' => $page->slug,
                'parent' => $page->parent,
                'title' => $page->title,
            ])
            ->all();
    }

    /**
     * Find a DB page record by slug.
     */
    public function findBySlug(string $slug): ?Model
    {
        return PageBuilder::$pageModel::where('slug', $slug)->first();
    }

    /**
     * Persist page meta fields to the database.
     *
     * Only non-null values are written so that empty strings do not
     * accidentally clear fields the editor never touched.
     */
    public function saveMeta(string $slug, array $meta): bool
    {
        if (empty($meta)) {
            return true;
        }

        $fillable = array_filter([
            'title' => $meta['title'] ?? null,
            'meta_title' => $meta['meta_title'] ?? null,
            'meta_description' => $meta['meta_description'] ?? null,
            'meta_keywords' => $meta['meta_keywords'] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($fillable)) {
            return true;
        }

        return (bool) PageBuilder::$pageModel::where('slug', $slug)->update($fillable);
    }
}
