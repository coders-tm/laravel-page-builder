<?php

declare(strict_types=1);

use PageBuilder\Collections\BlockCollection;
use PageBuilder\Collections\SectionCollection;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;

function makeSection(string $id, string $type = 'section', bool $disabled = false): Section
{
    return new Section([
        'id' => $id,
        'type' => $type,
        'disabled' => $disabled,
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);
}
test('empty collection', function () {
    $collection = new SectionCollection;

    expect($collection->isEmpty())->toBeTrue();
    expect($collection->count())->toBe(0);
});
test('enabled filters disabled sections', function () {
    $sections = [
        makeSection('s1', 'hero', false),
        makeSection('s2', 'footer', true),
        makeSection('s3', 'content', false),
    ];

    $collection = new SectionCollection($sections);
    $enabled = $collection->enabled();

    expect($enabled)->toBeInstanceOf(SectionCollection::class);
    expect($enabled->count())->toBe(2);
    expect($enabled->first()->id)->toBe('s1');
    expect($enabled->last()->id)->toBe('s3');
});
test('of type', function () {
    $sections = [
        makeSection('s1', 'hero'),
        makeSection('s2', 'hero'),
        makeSection('s3', 'footer'),
    ];

    $collection = new SectionCollection($sections);
    $heroes = $collection->ofType('hero');

    expect($heroes->count())->toBe(2);
});
test('find', function () {
    // SectionCollection::find() looks up by string key — items must be keyed by id
    $collection = new SectionCollection([
        's1' => makeSection('s1', 'hero'),
        's2' => makeSection('s2', 'footer'),
    ]);

    expect($collection->find('s1')->id)->toBe('s1');
    expect($collection->find('missing'))->toBeNull();
});
test('to array', function () {
    $sections = [
        makeSection('s1', 'hero'),
    ];

    $collection = new SectionCollection($sections);
    $array = $collection->toArray();

    expect($array)->toHaveCount(1);
    expect($array[0]['id'])->toBe('s1');
});
