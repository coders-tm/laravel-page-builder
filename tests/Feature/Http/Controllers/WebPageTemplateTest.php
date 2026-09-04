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

use PageBuilder\Facades\Page;
use Workbench\App\Models\Page as ModelsPage;

test('renders page using default template when no json exists', function () {
    ModelsPage::factory()->create([
        'slug' => 'template-no-json',
        'title' => 'Template Page',
        'content' => '<p>Hello from template</p>',
    ]);

    Page::routes();

    $response = $this->get(route('pages.template-no-json'));

    $response->assertOk();

    // Default template uses the page-content section which renders $page->content
    $response->assertSee('<p>Hello from template</p>', escape: false);
});
test('default template renders page content section', function () {
    ModelsPage::factory()->create([
        'slug' => 'default-tpl-page',
        'title' => 'Default Template',
        'content' => '<p>Default template content</p>',
    ]);

    Page::routes();

    $response = $this->get(route('pages.default-tpl-page'));

    $response->assertOk();

    $html = $response->getContent();

    // page-content section renders with prose class
    $this->assertStringContainsString('prose', $html);
    $this->assertStringContainsString('<p>Default template content</p>', $html);
});
test('renders page using selected template', function () {
    ModelsPage::factory()->create([
        'slug' => 'alternate-tpl-page',
        'title' => 'Alternate Template Page',
        'template' => 'page.alternate',
        'content' => '<p>Alternate content</p>',
    ]);

    Page::routes();

    $response = $this->get(route('pages.alternate-tpl-page'));

    $response->assertOk();
    $response->assertSee('<p>Alternate content</p>', escape: false);
});
test('renders wrapper element around sections', function () {
    // page.alternate.json has: "wrapper": "main#page-alternate.page-wrapper"
    ModelsPage::factory()->create([
        'slug' => 'wrapper-page',
        'title' => 'Wrapper Page',
        'template' => 'page.alternate',
        'content' => '<p>Wrapped</p>',
    ]);

    Page::routes();

    $html = $this->get(route('pages.wrapper-page'))->getContent();

    $this->assertStringContainsString('<main id="page-alternate" class="page-wrapper">', $html);
    $this->assertStringContainsString('</main>', $html);
    $this->assertStringContainsString('<p>Wrapped</p>', $html);
});
test('default template has no wrapper', function () {
    ModelsPage::factory()->create([
        'slug' => 'no-wrapper-page',
        'title' => 'No Wrapper',
        'content' => '<p>Content</p>',
    ]);

    Page::routes();

    $html = $this->get(route('pages.no-wrapper-page'))->getContent();

    // Default template has no wrapper — sections rendered directly
    $this->assertStringNotContainsString('<main id="page-alternate"', $html);
});
test('template settings resolve page title variable', function () {
    // page.var.json has "text": "{{ $page->title }}" in the banner section
    ModelsPage::factory()->create([
        'slug' => 'var-page',
        'title' => 'My Interpolated Title',
        'template' => 'page.var',
    ]);

    Page::routes();

    $html = $this->get(route('pages.var-page'))->getContent();

    // banner.blade.php renders $section->settings->text inside <h3>
    $this->assertStringContainsString('My Interpolated Title', $html);
});
test('template variable resolves to empty when attribute missing', function () {
    // meta_keywords is null by default in the factory
    ModelsPage::factory()->create([
        'slug' => 'missing-attr-page',
        'title' => 'Title OK',
        'template' => 'page.var',
    ]);

    Page::routes();

    $response = $this->get(route('pages.missing-attr-page'));
    $response->assertOk();
});
test('json file takes priority over template', function () {
    // layout-default.json exists in workbench pages dir and renders a banner section
    ModelsPage::factory()->create([
        'slug' => 'layout-default',
        'title' => 'JSON Priority Page',
        'content' => '<p>Should not appear from template</p>',
    ]);

    Page::routes();

    $html = $this->get(route('pages.layout-default'))->getContent();

    // layout-default.json renders a banner with text="Content"
    // The default template would render page-content (prose class)
    $this->assertStringContainsString('Content', $html);

    // The JSON file's banner section is what gets rendered, not the template
    $this->assertStringContainsString('class="banner', $html);
});
test('falls back to default template when selected not found', function () {
    ModelsPage::factory()->create([
        'slug' => 'fallback-tpl-page',
        'title' => 'Fallback Template',
        'template' => 'nonexistent-template',
        'content' => '<p>Fallback content</p>',
    ]);

    Page::routes();

    $response = $this->get(route('pages.fallback-tpl-page'));

    $response->assertOk();

    // Falls back to page.json template which renders page-content
    $response->assertSee('<p>Fallback content</p>', escape: false);
});
test('returns 404 when no page record exists', function () {
    // No DB record — template can't be resolved because we need a DB page
    // for the template system to look up its `template` field
    Page::routes();

    $response = $this->get('/totally-nonexistent-slug-xyz');
    $response->assertNotFound();
});
test('template page renders meta tags from db', function () {
    ModelsPage::factory()->create([
        'slug' => 'meta-template-page',
        'title' => 'Meta Template Page',
        'meta_title' => 'SEO Title',
        'meta_description' => 'SEO Description',
        'meta_keywords' => 'seo, template',
        'content' => '<p>Meta page content</p>',
    ]);

    Page::routes();

    $html = $this->get(route('pages.meta-template-page'))->getContent();

    // $meta_title overrides the title|app.name format when present
    $this->assertStringContainsString('SEO Title', $html);
    $this->assertStringContainsString('content="SEO Description"', $html);
    $this->assertStringContainsString('content="seo, template"', $html);
});
