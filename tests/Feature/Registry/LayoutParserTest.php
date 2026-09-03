<?php

declare(strict_types=1);

use PageBuilder\Registry\LayoutParser;

beforeEach(function () {
    $this->parser = $this->app->make(LayoutParser::class);
});
test('extract zoned keys', function () {
    $keys = $this->parser->extractZonedKeys('page');

    expect($keys['header'])->toBe(['header']);
    expect($keys['footer'])->toBe(['footer']);
});
test('default layout structure', function () {
    $layout = $this->parser->defaultLayout('simple');

    expect($layout['type'])->toBe('simple');
    expect($layout['header']['order'])->toBe(['announcement', 'header']);
    expect($layout['footer']['order'])->toBe(['footer']);

    expect($layout['header']['sections'])->toHaveKey('header');
    expect($layout['header']['sections']['header']['type'])->toBe('header');
});
test('extract zoned keys returns empty for missing layout', function () {
    $keys = $this->parser->extractZonedKeys('nonexistent');

    expect($keys['header'])->toBe([]);
    expect($keys['footer'])->toBe([]);
});
