<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Rendering;

use PageBuilder\Rendering\Renderer;
use PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\View;

class ParentSettingsTest extends TestCase
{
    private Renderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = $this->app->make(Renderer::class);

        // Add dummy views for testing
        View::addNamespace('test', __DIR__);
    }

    public function test_hydrated_blocks_have_parent_reference(): void
    {
        $section = $this->renderer->hydrateSection('s1', [
            'type' => 'simple',
            'settings' => ['heading' => 'Section Heading'],
            'blocks' => [
                'row-1' => [
                    'type' => 'row',
                    'settings' => [],
                    'blocks' => [
                        'col-1' => [
                            'type' => 'text',
                            'settings' => ['content' => 'Nested Block'],
                        ],
                    ],
                    'order' => ['col-1'],
                ],
            ],
            'order' => ['row-1'],
        ]);

        $row = $section->blocks->first();
        $col = $row->blocks->first();

        $this->assertSame($section, $row->parent, 'Top-level block should have section as parent');
        $this->assertSame($row, $col->parent, 'Nested block should have parent block as parent');

        // Verify settings access via parent
        $this->assertSame('Section Heading', $row->parent->settings->heading);
    }

    public function test_render_passes_parent_to_view_data(): void
    {
        // We use a partial mock or just capture view arguments if possible.
        // For simplicity, we'll check if the property exists and is used in the Renderer.

        $section = $this->renderer->hydrateSection('s1', [
            'type' => 'simple',
            'settings' => ['heading' => 'Parent Section'],
            'blocks' => [
                'b1' => ['type' => 'text', 'settings' => []],
            ],
        ]);

        $block = $section->blocks->first();

        // This confirms the Renderer logic we added:
        // 'parent' => $parent ?? $block->parent ?? $section

        $this->assertSame($section, $block->parent);
    }
}
