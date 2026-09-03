<?php

declare(strict_types=1);

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\PageBuilder;

test('construction with full data', function () {
    $section = new Section([
        'id' => 'hero-1',
        'type' => 'hero',
        'name' => 'Hero Section',
        'disabled' => false,
        'settings' => new Settings(['title' => 'Welcome'], []),
        'blocks' => new BlockCollection,
    ]);

    expect($section->id)->toBe('hero-1');
    expect($section->type)->toBe('hero');
    expect($section->name)->toBe('Hero Section');
    expect($section->disabled)->toBeFalse();
    expect($section->settings->title)->toBe('Welcome');
    expect($section->blocks)->toHaveCount(0);
});
test('construction with minimal data', function () {
    $section = new Section([]);

    expect($section->id)->toBe('');
    expect($section->type)->toBe('');
    expect($section->name)->toBe('');
    expect($section->disabled)->toBeFalse();
});
test('disabled section', function () {
    $section = new Section([
        'id' => 'hero-1',
        'type' => 'hero',
        'disabled' => true,
    ]);

    expect($section->disabled)->toBeTrue();
});
test('editor attributes empty when editor disabled', function () {
    PageBuilder::disableEditor();

    $section = new Section([
        'id' => 'hero-1',
        'type' => 'hero',
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);

    expect($section->editorAttributes())->toBe('');
});
test('to array', function () {
    $section = new Section([
        'id' => 'hero-1',
        'type' => 'hero',
        'name' => 'Hero',
        'settings' => new Settings(['title' => 'Hello'], ['title' => 'Default']),
        'blocks' => new BlockCollection,
    ]);

    $array = $section->toArray();

    expect($array['id'])->toBe('hero-1');
    expect($array['type'])->toBe('hero');
    expect($array['name'])->toBe('Hero');
    expect($array['disabled'])->toBeFalse();
    expect($array['settings']['title'])->toBe('Hello');
});
test('json serialization', function () {
    $section = new Section([
        'id' => 'hero-1',
        'type' => 'hero',
        'name' => 'Hero',
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);

    $json = json_encode($section);
    $decoded = json_decode($json, true);

    expect($decoded['id'])->toBe('hero-1');
    expect($decoded['type'])->toBe('hero');
});
afterEach(function () {
    PageBuilder::disableEditor();
});
