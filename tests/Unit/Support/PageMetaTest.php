<?php

declare(strict_types=1);

use PageBuilder\Support\PageMeta;

test('instantiates with null defaults when empty', function () {
    $meta = PageMeta::fromArray([]);

    expect($meta->isEmpty())->toBeTrue();
    expect($meta->title())->toBeNull();
    expect($meta->metaTitle())->toBeNull();
    expect($meta->metaDescription())->toBeNull();
    expect($meta->metaKeywords())->toBeNull();
    expect($meta->toArray())->toBe([]);
});

test('instantiates from raw array data', function () {
    $raw = [
        'title' => 'Sample Page',
        'meta_title' => 'SEO Title',
        'meta_description' => 'SEO Description',
        'meta_keywords' => 'laravel, pagebuilder',
    ];

    $meta = PageMeta::fromArray($raw);

    expect($meta->isEmpty())->toBeFalse();
    expect($meta->title())->toBe('Sample Page');
    expect($meta->metaTitle())->toBe('SEO Title');
    expect($meta->metaDescription())->toBe('SEO Description');
    expect($meta->metaKeywords())->toBe('laravel, pagebuilder');
    expect($meta->toArray())->toBe($raw);
});

test('array access works for array style property retrieval', function () {
    $meta = PageMeta::fromArray([
        'meta_title' => 'My Title',
    ]);

    expect(isset($meta['meta_title']))->toBeTrue();
    expect($meta['meta_title'])->toBe('My Title');
    expect(isset($meta['non_existent']))->toBeFalse();
    expect($meta['non_existent'])->toBeNull();
});

test('json serializes to array', function () {
    $meta = PageMeta::fromArray([
        'meta_title' => 'Json Title',
    ]);

    expect(json_encode($meta))->toBe(json_encode(['meta_title' => 'Json Title']));
});
