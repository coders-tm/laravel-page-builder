<?php

declare(strict_types=1);

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;
use PageBuilder\Rendering\Renderer;

beforeEach(function () {
    $this->renderer = $this->app->make(Renderer::class);
});
afterEach(function () {
    PageBuilder::disableEditor();
});
test('hydrate section', function () {
    $section = $this->renderer->hydrateSection('hero-1', [
        'type' => 'hero',
        'settings' => ['title' => 'Custom Title'],
        'blocks' => [],
    ]);

    expect($section)->toBeInstanceOf(Section::class);
    expect($section->id)->toBe('hero-1');
    expect($section->type)->toBe('hero');
    expect($section->name)->toBe('Hero');
    expect($section->settings->title)->toBe('Custom Title');

    // Default should apply for non-provided settings
    expect($section->settings->subtitle)->toBe('Hello World');
});
test('hydrate section with nested blocks', function () {
    $section = $this->renderer->hydrateSection('content-1', [
        'type' => 'content',
        'settings' => [],
        'blocks' => [
            'row-1' => [
                'type' => 'row',
                'settings' => ['columns' => '3'],
                'blocks' => [],
            ],
        ],
        'order' => ['row-1'],
    ]);

    expect($section->blocks->count())->toBe(1);
    expect($section->blocks->first()->id)->toBe('row-1');
    expect($section->blocks->first()->type)->toBe('row');
    expect($section->blocks->first()->settings->columns)->toBe('3');
});
test('hydrate section skips disabled blocks', function () {
    $section = $this->renderer->hydrateSection('test-1', [
        'type' => 'test',
        'settings' => [],
        'blocks' => [
            'b1' => ['type' => 'text', 'settings' => [], 'disabled' => false],
            'b2' => ['type' => 'text', 'settings' => [], 'disabled' => true],
        ],
        'order' => ['b1', 'b2'],
    ]);

    expect($section->blocks->count())->toBe(1);
    expect($section->blocks->first()->id)->toBe('b1');
});
test('render section', function () {
    $section = $this->renderer->hydrateSection('s1', [
        'type' => 'simple',
        'settings' => ['heading' => 'Test Heading'],
    ]);

    $html = $this->renderer->renderSection($section);

    $this->assertStringContainsString('<h1>Test Heading</h1>', $html);
    $this->assertStringContainsString('<section >', $html);
});
test('render section returns comment for unknown type', function () {
    $section = new Section([
        'id' => 's1',
        'type' => 'unknown',
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);

    $html = $this->renderer->renderSection($section);

    $this->assertStringContainsString('not found', $html);
});
test('render block', function () {
    $block = new Block([
        'id' => 'b1',
        'type' => 'text',
        'settings' => new Settings(['content' => 'Hello World'], []),
        'blocks' => new BlockCollection,
    ]);

    $html = $this->renderer->renderBlock($block);

    $this->assertStringContainsString('Hello World', $html);
    $this->assertStringContainsString('text-left', $html);
});
test('render block returns comment for unknown view', function () {
    $block = new Block([
        'id' => 'b1',
        'type' => 'nonexistent-block-type',
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);

    $html = $this->renderer->renderBlock($block);

    $this->assertStringContainsString('not found', $html);
});
test('render raw section', function () {
    $html = $this->renderer->renderRawSection('raw-1', [
        'type' => 'raw',
        'settings' => ['text' => 'Preview Text'],
    ]);

    $this->assertStringContainsString('Preview Text', $html);
});
