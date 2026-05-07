<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Services\PageRenderer;
use Coderstm\PageBuilder\Support\PageData;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PageRendererTest extends TestCase
{
    use RefreshDatabase;

    private PageRenderer $pageRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pageRenderer = $this->app->make(PageRenderer::class);
    }

    public function test_render_by_slug(): void
    {
        $this->get('/')->assertSee('Welcome Home');
    }

    public function test_render_page_from_array(): void
    {
        $html = $this->pageRenderer->renderPage([
            'sections' => [
                's1' => [
                    'type' => 'banner',
                    'settings' => ['text' => 'Array Page'],
                    'blocks' => [],
                ],
            ],
            'order' => ['s1'],
        ]);

        $this->assertStringContainsString('Array Page', $html);
    }

    public function test_render_page_from_page_data(): void
    {
        $pageData = PageData::fromArray([
            'sections' => [
                's1' => [
                    'type' => 'banner',
                    'settings' => ['text' => 'PageData Page'],
                    'blocks' => [],
                ],
            ],
            'order' => ['s1'],
        ]);

        $html = $this->pageRenderer->renderPage($pageData);

        $this->assertStringContainsString('PageData Page', $html);
    }

    public function test_render_page_renders_blade_syntax_in_section_and_block_settings(): void
    {
        $pageData = PageData::fromArray([
            'title' => 'Blade Settings Page',
            'sections' => [
                'banner-1' => [
                    'type' => 'banner',
                    'settings' => [
                        'text' => 'Section: {{ $page->title }} @if(config(\'app.name\') === \'My App\')ready @endif',
                    ],
                    'blocks' => [],
                    'order' => [],
                ],
                'content-1' => [
                    'type' => 'content',
                    'settings' => [],
                    'blocks' => [
                        'text-1' => [
                            'type' => 'text',
                            'settings' => [
                                'content' => 'Block: {{ strtoupper($page->title) }}',
                            ],
                            'blocks' => [],
                            'order' => [],
                        ],
                    ],
                    'order' => ['text-1'],
                ],
            ],
            'order' => ['banner-1', 'content-1'],
        ]);

        $html = $this->pageRenderer->renderPage($pageData);

        $this->assertStringContainsString('Section: Blade Settings Page ready', $html);
        $this->assertStringContainsString('Block: BLADE SETTINGS PAGE', $html);
        $this->assertStringNotContainsString('{{ $page->title }}', $html);
    }

    public function test_render_page_multiple_sections_in_order(): void
    {
        $html = $this->pageRenderer->renderPage([
            'sections' => [
                'banner-1' => [
                    'type' => 'banner',
                    'settings' => ['text' => 'First'],
                    'blocks' => [],
                ],
                'footer-1' => [
                    'type' => 'footer',
                    'blocks' => [],
                ],
            ],
            'order' => ['banner-1', 'footer-1'],
        ]);

        $bannerPos = strpos($html, 'First');
        $footerPos = strpos($html, 'All rights reserved.');

        $this->assertNotFalse($bannerPos);
        $this->assertNotFalse($footerPos);
        $this->assertLessThan($footerPos, $bannerPos);
    }

    public function test_render_page_skips_disabled_sections(): void
    {
        $html = $this->pageRenderer->renderPage([
            'sections' => [
                's1' => [
                    'type' => 'banner',
                    'settings' => ['text' => 'Visible'],
                    'blocks' => [],
                ],
                's2' => [
                    'type' => 'footer',
                    'settings' => ['copyright' => 'Hidden'],
                    'blocks' => [],
                    'disabled' => true,
                ],
            ],
            'order' => ['s1', 's2'],
        ]);

        $this->assertStringContainsString('Visible', $html);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    public function test_render_page_skips_missing_section_ids(): void
    {
        $html = $this->pageRenderer->renderPage([
            'sections' => [
                's1' => [
                    'type' => 'banner',
                    'settings' => ['text' => 'Only One'],
                    'blocks' => [],
                ],
            ],
            'order' => ['s1', 'nonexistent-section'],
        ]);

        $this->assertStringContainsString('Only One', $html);
    }

    public function test_render_empty_page(): void
    {
        $html = $this->pageRenderer->renderPage([
            'sections' => [],
            'order' => [],
        ]);

        $this->assertEmpty($html);
    }
}
