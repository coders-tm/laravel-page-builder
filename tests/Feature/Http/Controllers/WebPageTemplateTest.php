<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Http\Controllers;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Models\Page as ModelsPage;

/**
 * Tests template-based page rendering.
 *
 * Resolution order:
 *   1. Custom Blade view (pages/{slug}.blade.php)
 *   2. Page JSON (pages/{slug}.json)
 *   3. Template JSON (templates/{template}.json or templates/page.json)
 *   4. 404
 */
class WebPageTemplateTest extends TestCase
{
    use RefreshDatabase;

    // ── Default template (templates/page.json) ────────────────────────────────

    public function test_renders_page_using_default_template_when_no_json_exists(): void
    {
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
    }

    public function test_default_template_renders_page_content_section(): void
    {
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
    }

    // ── Selected template ────────────────────────────────────────────────────

    public function test_renders_page_using_selected_template(): void
    {
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
    }

    // ── Wrapper property ─────────────────────────────────────────────────────

    public function test_renders_wrapper_element_around_sections(): void
    {
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
    }

    public function test_default_template_has_no_wrapper(): void
    {
        ModelsPage::factory()->create([
            'slug' => 'no-wrapper-page',
            'title' => 'No Wrapper',
            'content' => '<p>Content</p>',
        ]);

        Page::routes();

        $html = $this->get(route('pages.no-wrapper-page'))->getContent();

        // Default template has no wrapper — sections rendered directly
        $this->assertStringNotContainsString('<main id="page-alternate"', $html);
    }

    // ── Variable interpolation ───────────────────────────────────────────────

    public function test_template_settings_resolve_page_title_variable(): void
    {
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
    }

    public function test_template_variable_resolves_to_empty_when_attribute_missing(): void
    {
        // meta_keywords is null by default in the factory
        ModelsPage::factory()->create([
            'slug' => 'missing-attr-page',
            'title' => 'Title OK',
            'template' => 'page.var',
        ]);

        Page::routes();

        $response = $this->get(route('pages.missing-attr-page'));
        $response->assertOk();
    }

    // ── Priority: Blade > JSON > Template ────────────────────────────────────

    public function test_json_file_takes_priority_over_template(): void
    {
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
    }

    // ── Fallback: unknown template → default page.json template ──────────────

    public function test_falls_back_to_default_template_when_selected_not_found(): void
    {
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
    }

    // ── 404 when no template exists ──────────────────────────────────────────

    public function test_returns_404_when_no_page_record_exists(): void
    {
        // No DB record — template can't be resolved because we need a DB page
        // for the template system to look up its `template` field
        Page::routes();

        $response = $this->get('/totally-nonexistent-slug-xyz');
        $response->assertNotFound();
    }

    // ── Meta tags from DB page ───────────────────────────────────────────────

    public function test_template_page_renders_meta_tags_from_db(): void
    {
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
    }
}
