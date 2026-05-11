<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class PageEditorTemplateFallbackTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'editor-fallback-test';

    private const TEMPLATE_NAME = 'fallback-test-template';

    protected function setUp(): void
    {
        parent::setUp();

        $templatesPath = config('pagebuilder.templates');

        // Create a template file
        if (! File::isDirectory($templatesPath)) {
            File::makeDirectory($templatesPath, 0755, true);
        }

        File::put($templatesPath.'/'.self::TEMPLATE_NAME.'.json', json_encode([
            'sections' => [
                'template-section' => [
                    'type' => 'banner',
                    'settings' => ['text' => 'Content from Template'],
                ],
            ],
            'order' => ['template-section'],
        ]));
    }

    protected function tearDown(): void
    {
        $templatesPath = config('pagebuilder.templates');
        $pagesPath = config('pagebuilder.pages');

        @unlink($templatesPath.'/'.self::TEMPLATE_NAME.'.json');
        @unlink($pagesPath.'/'.self::SLUG.'.json');

        parent::tearDown();
    }

    public function test_editor_mode_uses_template_content_when_json_is_missing(): void
    {
        // Create a DB page record specifying our template
        \Coderstm\PageBuilder\Models\Page::create([
            'title' => 'Test Page',
            'slug' => self::SLUG,
            'template' => self::TEMPLATE_NAME,
            'is_active' => true,
        ]);

        // Ensure no page JSON exists
        $this->assertFileDoesNotExist(config('pagebuilder.pages').'/'.self::SLUG.'.json');

        // Render in editor mode via query param
        $view = Page::render(self::SLUG, ['pb-editor' => '1']);
        $data = $view->getData();

        // Verify that __pb_content contains the template content
        $this->assertStringContainsString('Content from Template', (string) $data['__pb_content']);

        // Verify that __pb_layout contains the template data
        $this->assertTrue($data['__pb_layout']->isNotEmpty());
        $this->assertArrayHasKey('template-section', $data['__pb_layout']->sections());
    }

    public function test_editor_json_response_includes_template_content_when_json_is_missing(): void
    {
        // Create a DB page record specifying our template
        \Coderstm\PageBuilder\Models\Page::create([
            'title' => 'Test Page',
            'slug' => self::SLUG,
            'template' => self::TEMPLATE_NAME,
            'is_active' => true,
        ]);

        // Ensure no page JSON exists
        $this->assertFileDoesNotExist(config('pagebuilder.pages').'/'.self::SLUG.'.json');

        // Request the page JSON
        $response = $this->getJson('/pagebuilder/'.self::SLUG.'.json');

        $response->assertStatus(200);
        $response->assertJsonPath('sections.template-section.type', 'banner');
        $response->assertJsonPath('sections.template-section.settings.text', 'Content from Template');
        $response->assertJsonPath('order.0', 'template-section');
    }

    public function test_normal_render_uses_template_fallback_when_json_is_missing(): void
    {
        // Create a DB page record specifying our template
        \Coderstm\PageBuilder\Models\Page::create([
            'title' => 'Test Page',
            'slug' => self::SLUG,
            'template' => self::TEMPLATE_NAME,
            'is_active' => true,
        ]);

        // Ensure no page JSON exists
        $this->assertFileDoesNotExist(config('pagebuilder.pages').'/'.self::SLUG.'.json');

        // Render in normal mode
        $view = Page::render(self::SLUG, []);
        $data = $view->getData();

        // Verify that __pb_content contains the template content
        $this->assertStringContainsString('Content from Template', (string) $data['__pb_content']);
    }
}
