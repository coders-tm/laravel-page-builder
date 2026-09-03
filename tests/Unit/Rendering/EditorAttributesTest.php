<?php

declare(strict_types=1);

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
