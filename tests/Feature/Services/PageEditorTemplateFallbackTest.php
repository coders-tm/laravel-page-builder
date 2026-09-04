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

use Illuminate\Support\Facades\File;
use PageBuilder\Facades\Page;

const SLUG = 'editor-fallback-test';
const TEMPLATE_NAME = 'fallback-test-template';

beforeEach(function () {
    $templatesPath = config('pagebuilder.templates');

    // Create a template file
    if (! File::isDirectory($templatesPath)) {
        File::makeDirectory($templatesPath, 0755, true);
    }

    File::put($templatesPath.'/'.TEMPLATE_NAME.'.json', json_encode([
        'sections' => [
            'template-section' => [
                'type' => 'banner',
                'settings' => ['text' => 'Content from Template'],
            ],
        ],
        'order' => ['template-section'],
    ]));
});
afterEach(function () {
    $templatesPath = config('pagebuilder.templates');
    $pagesPath = config('pagebuilder.pages');

    @unlink($templatesPath.'/'.TEMPLATE_NAME.'.json');
    @unlink($pagesPath.'/'.SLUG.'.json');

});
test('editor mode uses template content when json is missing', function () {
    // Create a DB page record specifying our template
    PageBuilder\Models\Page::create([
        'title' => 'Test Page',
        'slug' => SLUG,
        'template' => TEMPLATE_NAME,
        'is_active' => true,
    ]);

    // Ensure no page JSON exists
    $this->assertFileDoesNotExist(config('pagebuilder.pages').'/'.SLUG.'.json');

    // Render in editor mode via query param
    $view = Page::render(SLUG, ['pb-editor' => '1']);
    $data = $view->getData();

    // Verify that __pb_content contains the template content
    $this->assertStringContainsString('Content from Template', (string) $data['__pb_content']);

    // Verify that __pb_layout contains the template data
    expect($data['__pb_layout']->isNotEmpty())->toBeTrue();
    expect($data['__pb_layout']->sections())->toHaveKey('template-section');
});
test('editor json response includes template content when json is missing', function () {
    // Create a DB page record specifying our template
    PageBuilder\Models\Page::create([
        'title' => 'Test Page',
        'slug' => SLUG,
        'template' => TEMPLATE_NAME,
        'is_active' => true,
    ]);

    // Ensure no page JSON exists
    $this->assertFileDoesNotExist(config('pagebuilder.pages').'/'.SLUG.'.json');

    // Request the page JSON
    $response = $this->getJson('/pagebuilder/'.SLUG.'.json');

    $response->assertStatus(200);
    $response->assertJsonPath('sections.template-section.type', 'banner');
    $response->assertJsonPath('sections.template-section.settings.text', 'Content from Template');
    $response->assertJsonPath('order.0', 'template-section');
});
test('normal render uses template fallback when json is missing', function () {
    // Create a DB page record specifying our template
    PageBuilder\Models\Page::create([
        'title' => 'Test Page',
        'slug' => SLUG,
        'template' => TEMPLATE_NAME,
        'is_active' => true,
    ]);

    // Ensure no page JSON exists
    $this->assertFileDoesNotExist(config('pagebuilder.pages').'/'.SLUG.'.json');

    // Render in normal mode
    $view = Page::render(SLUG, []);
    $data = $view->getData();

    // Verify that __pb_content contains the template content
    $this->assertStringContainsString('Content from Template', (string) $data['__pb_content']);
});
