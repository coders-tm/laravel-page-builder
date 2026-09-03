<?php

declare(strict_types=1);

namespace PageBuilder\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PageBuilder\Http\Controllers\WebPageController;
use PageBuilder\PageBuilder;
use PageBuilder\Registry\LayoutParser;
use PageBuilder\Support\PageData;
use PageBuilder\Support\TemplateVariableResolver;

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
     * @param  array<string, string|null>  $meta  Optional overrides for title, meta_title, meta_description, meta_keywords
     */
    public function render(string $slug, array $meta = [], bool $editor = false): mixed
    {
        if (! preg_match('#^[a-z0-9\-_/]+$#i', $slug)) {
            abort(404);
        }

        $dbPage = $this->findBySlug($slug);

        // Render the blade snippets in the page's content
        if ($dbPage?->content) {
            $dbPage->content = Blade::render($dbPage->content);
        }

        // Share the DB page model with all views rendered in this request,
        // so section views (e.g. page-content) can access $page->title, $page->content, etc.
        View::share('page', $dbPage);

        // ── 0. Editor frame mode ──────────────────────────────────────────
        // Load the editor frame only when explicitly requested.
        if ($editor) {
            return view('pagebuilder::layout', [
                'config' => PageBuilder::scriptVariables(),
            ]);
        }

        [$page, $isResolved] = $this->resolve($slug, $dbPage);

        // ── 1. Custom page view ───────────────────────────────────────────
        if (View::exists("pages.{$slug}")) {
            return view("pages.{$slug}", [
                ...$this->pageMeta($dbPage, $page, $meta),
                'slug' => $slug,
                '__pb_layout' => $page,
            ]);
        }

        // ── 2. Editor mode ────────────────────────────────────────────────
        if ($editor || PageBuilder::editor()) {
            return view('pagebuilder::page', [
                ...$this->pageMeta($dbPage, $page, $meta),
                'slug' => $slug,
                '__pb_content' => request()->boolean('pb-preview')
                    ? $this->editorPreviewShell->render()
                    : $this->pageRenderer->renderPage($page, editor: true),
                '__pb_layout' => $page,
            ]);
        }

        // ── 3. Page builder JSON / Template ───────────────────────────────
        if ($isResolved) {
            return $this->renderPage($slug, $page, $dbPage, $meta);
        }

        // ── 4. Nothing found ──────────────────────────────────────────────
        abort(404);
    }

    /**
     * Resolve a PageData instance for the given slug, trying stored JSON first,
     * then template fallback, and finally a blank page.
     *
     * @return array{0: PageData, 1: bool}
     */
    public function resolve(string $slug, ?Model $dbPage = null): array
    {
        $stored = $this->pageStorage->load($slug);

        if ($stored !== null) {
            $layoutType = $stored->layoutType() ?? 'page';
            $defaultLayout = $this->layoutParser->defaultLayout($layoutType);

            return [$this->buildPage($stored, $defaultLayout, $dbPage), true];
        }

        $templateData = $this->resolveTemplate($dbPage);

        if ($templateData !== null) {
            $resolvedData = $this->variableResolver->resolve($templateData, $dbPage);
            $templateLayout = $this->resolveTemplateLayout($resolvedData);

            return [$this->buildPageFromTemplate($resolvedData, $templateLayout, $dbPage), true];
        }

        $defaultLayout = $this->layoutParser->defaultLayout('page');

        return [$this->buildPage(null, $defaultLayout, $dbPage), false];
    }

    /**
     * Render a page from page builder data.
     */
    protected function renderPage(string $slug, PageData $page, mixed $dbPage, array $meta): mixed
    {
        $html = $this->pageRenderer->renderPage($page);

        return view('pagebuilder::page', [
            ...$this->pageMeta($dbPage, $page, $meta),
            'slug' => $slug,
            '__pb_content' => $html,
            '__pb_layout' => $page,
        ]);
    }

    /**
     * Resolve template data for the given DB page.
     *
     * Tries the page's own template first; falls back to the default "page"
     * template. Returns null when neither template file exists.
     *
     * @return array<string, mixed>|null
     */
    protected function resolveTemplate(mixed $dbPage): ?array
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
    protected function resolveTemplateLayout(array $templateData): array
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
    protected function buildPageFromTemplate(array $templateData, array $defaultLayout, mixed $dbPage): PageData
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
    protected function buildPage(?PageData $stored, array $defaultLayout, mixed $dbPage): PageData
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
    protected function pageMeta(mixed $dbPage, ?PageData $stored = null, array $meta = []): array
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

        foreach (app(PageRegistry::class)->pages() as $path => $page) {
            if ($path) {
                Route::get($path, [WebPageController::class, 'pages'])
                    ->defaults('slug', $path)
                    ->name('pages.'.str_replace('/', '.', $path));
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
            ->whereNotIn('slug', config('pagebuilder.reserved_slugs', [
                'home',
                'admin',
                'user',
                'api',
                'storage',
                'uploads',
                'files',
                'vendor',
            ]))
            ->get()
            ->mapWithKeys(function ($page) {
                $path = $page->parent ? "{$page->parent}/{$page->slug}" : $page->slug;

                return [$path => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'parent' => $page->parent,
                    'title' => $page->title,
                    'path' => $path,
                ]];
            })
            ->all();
    }

    /**
     * Find a DB page record by slug.
     */
    public function findBySlug(string $slug): ?Model
    {
        if (str_contains($slug, '/')) {
            $paths = explode('/', $slug);
            $slugPart = array_pop($paths);
            $parentPath = implode('/', $paths);

            return PageBuilder::$pageModel::where('slug', $slugPart)
                ->where('parent', $parentPath)
                ->first();
        }

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

        $page = $this->findBySlug($slug);

        if (! $page) {
            return false;
        }

        return (bool) $page->update($fillable);
    }
}
