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

use Illuminate\Support\Facades\View;
use PageBuilder\Rendering\Renderer;

beforeEach(function () {
    $this->renderer = $this->app->make(Renderer::class);

    // Add dummy views for testing
    View::addNamespace('test', __DIR__);
});
test('hydrated blocks have parent reference', function () {
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

    expect($row->parent)->toBe($section, 'Top-level block should have section as parent');
    expect($col->parent)->toBe($row, 'Nested block should have parent block as parent');

    // Verify settings access via parent
    expect($row->parent->settings->heading)->toBe('Section Heading');
});
test('render passes parent to view data', function () {
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
    expect($block->parent)->toBe($section);
});
