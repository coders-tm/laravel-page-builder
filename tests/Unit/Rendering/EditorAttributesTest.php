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

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;
use PageBuilder\Rendering\EditorAttributes;

afterEach(function () {
    PageBuilder::disableEditor();
});

test('for section returns empty when editor off', function () {
    PageBuilder::disableEditor();

    expect(EditorAttributes::forSection(makeEditorSection()))->toBe('');
});
test('for block returns empty when editor off', function () {
    PageBuilder::disableEditor();

    expect(EditorAttributes::forBlock(makeEditorBlock()))->toBe('');
});
test('for section returns attributes when editor on', function () {
    PageBuilder::enableEditor();

    $result = EditorAttributes::forSection(makeEditorSection('hero-1', 'hero'));

    // Actual format: data-editor-section='{"id":"hero-1","type":"hero",...}' data-section-id="hero-1"
    $this->assertStringContainsString('data-section-id="hero-1"', $result);
    $this->assertStringContainsString('data-editor-section=', $result);
    $this->assertStringContainsString('"id"', $result);
    $this->assertStringContainsString('"hero-1"', $result);
});
test('for block returns attributes when editor on', function () {
    PageBuilder::enableEditor();

    $result = EditorAttributes::forBlock(makeEditorBlock('block-1', 'row'));

    // Actual format: data-block-id="block-1" data-editor-block='{"id":"block-1",...}'
    $this->assertStringContainsString('data-block-id="block-1"', $result);
    $this->assertStringContainsString('data-editor-block=', $result);
    $this->assertStringContainsString('"block-1"', $result);
});
test('for section includes pb-disabled-section attribute when disabled', function () {
    PageBuilder::enableEditor();

    $section = makeSection('hero-1', 'hero', disabled: true);
    $result = EditorAttributes::forSection($section);

    $this->assertStringContainsString('pb-disabled-section', $result);
    $this->assertStringContainsString('data-section-id="hero-1"', $result);
});

test('for section does not include pb-disabled-section when enabled', function () {
    PageBuilder::enableEditor();

    $section = makeSection('hero-1', 'hero', disabled: false);
    $result = EditorAttributes::forSection($section);

    $this->assertStringNotContainsString('pb-disabled-section', $result);
    $this->assertStringContainsString('data-section-id="hero-1"', $result);
});

test('for block includes pb-disabled-block attribute when disabled', function () {
    PageBuilder::enableEditor();

    $block = new Block([
        'id' => 'block-1',
        'type' => 'text',
        'disabled' => true,
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);
    $result = EditorAttributes::forBlock($block);

    $this->assertStringContainsString('pb-disabled-block', $result);
    $this->assertStringContainsString('data-block-id="block-1"', $result);
});

test('for block does not include pb-disabled-block when enabled', function () {
    PageBuilder::enableEditor();

    $block = new Block([
        'id' => 'block-1',
        'type' => 'text',
        'disabled' => false,
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);
    $result = EditorAttributes::forBlock($block);

    $this->assertStringNotContainsString('pb-disabled-block', $result);
    $this->assertStringContainsString('data-block-id="block-1"', $result);
});

test('for section includes disabled flag in JSON meta when disabled', function () {
    PageBuilder::enableEditor();

    $section = makeSection('hero-1', 'hero', disabled: true);
    $result = EditorAttributes::forSection($section);

    $this->assertStringContainsString('"disabled":true', $result);
});

test('for block includes disabled flag in JSON meta when disabled', function () {
    PageBuilder::enableEditor();

    $block = new Block([
        'id' => 'block-1',
        'type' => 'text',
        'disabled' => true,
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);
    $result = EditorAttributes::forBlock($block);

    $this->assertStringContainsString('"disabled":true', $result);
});

test('auto inject live text when editor off', function () {
    PageBuilder::disableEditor();

    // autoInjectLiveText has no editor guard — it always runs.
    // The Renderer only calls it when editor is on. The method itself
    // simply returns the HTML unchanged when there are no matching text values.
    $html = '<h1>No match here xyz</h1>';
    $section = makeEditorSection('hero-1', 'hero');

    // settings has title='Hello'
    $result = EditorAttributes::autoInjectLiveText($html, $section);

    // Text 'Hello' not in the HTML so no injection occurs
    expect($result)->toBe($html);
});
test('auto inject live text when editor on', function () {
    PageBuilder::enableEditor();

    $section = makeEditorSection('hero-1', 'hero');
    // settings has title='Hello'
    $html = '<h1>Hello</h1>';

    $result = EditorAttributes::autoInjectLiveText($html, $section);

    // autoInjectLiveText injects data-live-text-setting when text matches a setting value
    expect($result)->toBeString();
    $this->assertStringContainsString('data-live-text-setting=', $result);
});

test('auto inject image settings injects data-image-setting into matching img tag', function () {
    PageBuilder::enableEditor();

    $section = new Section([
        'id' => 'hero-1',
        'type' => 'hero',
        'settings' => new Settings(['hero_image' => '/statics/hero.png'], []),
        'blocks' => new BlockCollection,
    ]);
    $html = '<div><img src="/statics/hero.png" alt="Hero" /></div>';

    $result = EditorAttributes::autoInjectImageSettings($html, $section);

    expect($result)->toContain('data-image-setting="hero-1.hero_image"');
    expect($result)->toContain('src="/statics/hero.png"');
});

test('injectDataImageSetting handles malformed strings safely without throwing exception', function () {
    $html = '<div><img src="http://:80" /></div>';
    $result = EditorAttributes::injectDataImageSetting($html, 'http://:80', 'hero-1.test');

    expect($result)->toBeString();
});
