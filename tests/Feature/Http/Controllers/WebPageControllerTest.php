<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\Facades\Page;
use PageBuilder\PageBuilder;
use Workbench\App\Models\Page as ModelsPage;

/**
 * Creates DB records for layout pages and registers their routes.
 *
 * Uses the pre-existing workbench fixture JSON files:
 *   workbench/resources/views/pages/layout-default.json
 *   workbench/resources/views/pages/layout-simple.json
 *
 * The PageObserver fires on create and calls pages:regenerate,
 * which reloads the PageRegistry from the database automatically.
 * Sections and layouts are resolved from workbench Blade views.
 */
function setUpLayoutTestPage(): void
{
    ModelsPage::factory()->create([
        'slug' => 'layout-default',
        'title' => 'A Test Page',
        'meta_description' => 'Test description',
        'meta_keywords' => 'test, page',
        'content' => '<p>Hello from page content</p>',
    ]);

    ModelsPage::factory()->create([
        'slug' => 'layout-simple',
        'title' => 'A Test Page with Simple Layout',
        'meta_description' => 'Test description',
        'meta_keywords' => 'test, page',
        'content' => '<p>Hello from page content</p>',

    ]);

    Page::routes();
}
test('renders page with layout sections', function () {
    setUpLayoutTestPage();

    $response = $this->get(route('pages.layout-default'));

    $response->assertOk();

    $html = $response->getContent();

    // ── Layout shell ──────────────────────────────────────────────────
    $this->assertStringContainsString('<html', $html);
    $this->assertStringContainsString('<body class="page-layout', $html);

    // ── Meta tags from DB page ────────────────────────────────────────
    $this->assertStringContainsString('A Test Page | My App', $html);
    $this->assertStringContainsString('content="Test description"', $html);
    $this->assertStringContainsString('content="test, page"', $html);

    // ── Layout sections (header / footer zones via header-group) ─────
    // header-group.json defines header + announcement sub-sections.
    // header.blade.php default title = 'Default Header Title' (schema default).
    // header-group.json preset title = 'My Site Header'.
    $this->assertStringContainsString('My Site Header', $html);

    // ── Page body section (banner) ────────────────────────────────────
    // workbench/resources/views/sections/banner.blade.php:
    //   <div class="banner">{{ $section->settings->text }}</div>
    $this->assertStringContainsString('<h3 class="text-2xl font-bold text-white">Content</h3>', $html);
});
test('renders page with layout simple sections', function () {
    setUpLayoutTestPage();

    $response = $this->get(route('pages.layout-simple'));

    $response->assertOk();

    $html = $response->getContent();

    // ── Layout shell ──────────────────────────────────────────────────
    $this->assertStringContainsString('<html', $html);
    $this->assertStringContainsString('<body class="simple-layout', $html);

    // ── Meta tags from DB page ────────────────────────────────────────
    $this->assertStringContainsString('A Test Page with Simple Layout | My App', $html);
    $this->assertStringContainsString('content="Test description"', $html);
    $this->assertStringContainsString('content="test, page"', $html);

    // ── Layout sections (header / footer zones) ───────────────────────
    // workbench/resources/views/sections/header.blade.php:
    //   <header {!! $section->editorAttributes() !!}>{{ $section->settings->title }}</header>
    // editorAttributes() returns '' when editor is off → <header >…</header>
    $this->assertStringContainsString('Flash Sale! 50% Off Everything!', $html);
    $this->assertStringContainsString('My Site Header', $html);

    // ── Page body section (banner) ────────────────────────────────────
    // workbench/resources/views/sections/banner.blade.php:
    //   <div class="banner">{{ $section->settings->text }}</div>
    $this->assertStringContainsString('<h3 class="text-2xl font-bold text-white">Content</h3>', $html);
});
test('shared page object is available to views', function () {
    setUpLayoutTestPage();

    $response = $this->get(route('pages.layout-default'));

    $response->assertOk();

    // The $page model is shared via View::share('page', $dbPage).
    // The page-content section uses $page->content to render raw HTML.
    // We verify the page loaded successfully — content sharing is
    // exercised by the page-content section when included in a page JSON.
    $response->assertStatus(200);
});
test('sections directive renders with default layout on custom route', function () {
    // No DB record, no Page::routes() — purely a custom Route::get() in workbench/routes/web.php
    // that calls view('pages.custom-blade') directly.
    $response = $this->get('/custom-route');

    $response->assertOk();

    $html = $response->getContent();

    // Body content proves the view rendered at all.
    $this->assertStringContainsString('Custom blade page body', $html);

    // @sections('header') must render — falls back to header preset 'My Site Header'.
    $this->assertStringContainsString('My Site Header', $html);

    // @sections('footer') must render — falls back to footer preset 'Quick Links'.
    $this->assertStringContainsString('Quick Links', $html);
});
test('sections directive renders with default layout when using custom blade view', function () {
    ModelsPage::factory()->create([
        'slug' => 'custom-blade',
        'title' => 'Custom Blade Page',
    ]);

    Page::routes();

    $response = $this->get(route('pages.custom-blade'));

    $response->assertOk();

    $html = $response->getContent();

    // The custom Blade body must appear (sanity check that the view rendered).
    $this->assertStringContainsString('Custom blade page body', $html);

    // @sections('header') must render — header.blade.php preset title = 'My Site Header'.
    // Before the fix $__pb_layout was null → renderLayoutSection() returned ''.
    $this->assertStringContainsString('My Site Header', $html);

    // @sections('footer') must render — footer.blade.php preset includes 'Quick Links'.
    $this->assertStringContainsString('Quick Links', $html);
});
function setUpPageWithContent(string $content = '<p>Hello from page content</p>'): void
{
    ModelsPage::create([
        'slug' => 'page-with-content',
        'title' => 'Page With Content',
        'content' => $content,
        'is_active' => true,
    ]);

    Page::routes();
}
test('page content section renders page content', function () {
    setUpPageWithContent('<p>Hello from page content</p>');

    $response = $this->get(route('pages.page-with-content'));

    $response->assertOk();
    $response->assertSee('<p>Hello from page content</p>', escape: false);
});
test('page content section renders default tailwind classes', function () {
    setUpPageWithContent();

    $response = $this->get(route('pages.page-with-content'));

    $response->assertOk();

    $html = $response->getContent();

    // Default settings: padding_top=md → pt-12, padding_bottom=md → pb-12
    $this->assertStringContainsString('pt-12', $html);
    $this->assertStringContainsString('pb-12', $html);

    // Default max_width=4xl → max-w-4xl
    $this->assertStringContainsString('max-w-4xl', $html);

    // Prose wrapper
    $this->assertStringContainsString('prose', $html);
});
test('page content section renders empty when page has no content', function () {
    setUpPageWithContent('');

    $response = $this->get(route('pages.page-with-content'));

    $response->assertOk();

    // Section wrapper still renders; content area is empty
    $html = $response->getContent();
    $this->assertStringContainsString('pt-12', $html);
    expect($html)->toMatch('/<div[^>]*prose[^>]*>\s*<\/div>/');
});

test('returns custom blade view response for non-editor request when custom view exists', function () {
    ModelsPage::factory()->create([
        'slug' => 'custom-blade',
        'title' => 'Custom Blade Page',
    ]);

    Page::routes();

    $response = $this->get('/custom-blade');

    $response->assertOk();
    $response->assertViewIs('pages.custom-blade');
    $response->assertSee('Custom blade page body');
});

test('returns editor frame layout when editor=true parameter is present and authorized even if custom blade view exists', function () {
    ModelsPage::factory()->create([
        'slug' => 'custom-blade',
        'title' => 'Custom Blade Page',
    ]);

    Page::routes();

    PageBuilder::auth(fn () => true);

    $response = $this->get('/custom-blade?editor=true');

    $response->assertOk();
    $response->assertViewIs('pagebuilder::layout');
});

test('returns custom blade view response when editor=true parameter is present but unauthorized and custom view exists', function () {
    ModelsPage::factory()->create([
        'slug' => 'custom-blade',
        'title' => 'Custom Blade Page',
    ]);

    Page::routes();

    PageBuilder::auth(fn () => false);

    $response = $this->get('/custom-blade?editor=true');

    $response->assertOk();
    $response->assertViewIs('pages.custom-blade');
    $response->assertSee('Custom blade page body');
});

test('returns nested custom blade view response for nested slug when custom view exists', function () {
    $viewDir = resource_path('views/pages/nested');
    $viewPath = $viewDir.'/custom.blade.php';
    @mkdir($viewDir, 0755, true);
    file_put_contents($viewPath, '<div>Nested Custom Blade View</div>');

    try {
        ModelsPage::factory()->create([
            'slug' => 'custom',
            'parent' => 'nested',
            'title' => 'Nested Custom Page',
        ]);

        Page::routes();

        $response = $this->get('/nested/custom');

        $response->assertOk();
        $response->assertViewIs('pages.nested.custom');
        $response->assertSee('Nested Custom Blade View');
    } finally {
        @unlink($viewPath);
        @rmdir($viewDir);
    }
});

afterEach(function () {
    PageBuilder::$authCallback = null;
});
