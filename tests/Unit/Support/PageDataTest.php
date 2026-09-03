<?php

declare(strict_types=1);
use PageBuilder\Support\PageData;

function sampleData(): array
{
    return [
        'sections' => [
            'hero' => [
                'type' => 'hero',
                'settings' => ['title' => 'Welcome'],
                'blocks' => [],
                'order' => [],
                'disabled' => false,
            ],
            'footer' => [
                'type' => 'footer',
                'settings' => [],
                'blocks' => [],
                'order' => [],
                'disabled' => false,
            ],
        ],
        'order' => ['hero', 'footer'],
        'title' => 'Home Page',
    ];
}
function sampleDataWithLayout(): array
{
    $data = sampleData();
    $data['layout'] = [
        'type' => 'page',
        'header' => [
            'sections' => [
                'header' => [
                    'type' => 'header',
                    'disabled' => false,
                    'settings' => ['logo' => '/img/logo.png', 'menu' => 'menu-1', 'sticky' => true],
                    'blocks' => [],
                    'order' => [],
                ],
                'disabled-section' => [
                    'type' => 'promo',
                    'disabled' => true,
                    'settings' => ['text' => 'Promo text'],
                ],
            ],
            'order' => ['header', 'disabled-section'],
        ],
        'footer' => [
            'sections' => [
                'footer' => [
                    'type' => 'footer',
                    'disabled' => false,
                    'settings' => ['tagline' => 'Best gym ever'],
                ],
            ],
            'order' => ['footer'],
        ],
    ];

    return $data;
}
test('from array', function () {
    $data = PageData::fromArray(sampleData());

    expect($data)->toBeInstanceOf(PageData::class);
    expect($data->title())->toBe('Home Page');
});
test('from array with empty array', function () {
    $data = PageData::fromArray([]);

    expect($data->isEmpty())->toBeTrue();
    expect($data->order())->toBe([]);
    expect($data->sections())->toBe([]);
});
test('from array with missing keys', function () {
    $data = PageData::fromArray(['foo' => 'bar']);

    expect($data->isEmpty())->toBeTrue();
    expect($data->title())->toBe('');
    expect($data->sections())->toBe([]);
});
test('order', function () {
    $data = PageData::fromArray(sampleData());

    expect($data->order())->toBe(['hero', 'footer']);
});
test('sections', function () {
    $data = PageData::fromArray(sampleData());

    $sections = $data->sections();
    expect($sections)->toHaveCount(2);
    expect($sections)->toHaveKey('hero');
    expect($sections)->toHaveKey('footer');
});
test('section by id', function () {
    $data = PageData::fromArray(sampleData());

    $hero = $data->section('hero');
    expect($hero['type'])->toBe('hero');
    expect($hero['settings']['title'])->toBe('Welcome');
});
test('section returns null for missing id', function () {
    $data = PageData::fromArray(sampleData());

    expect($data->section('nonexistent'))->toBeNull();
});
test('title', function () {
    $data = PageData::fromArray(sampleData());

    expect($data->title())->toBe('Home Page');
});
test('title defaults to empty string', function () {
    $data = PageData::fromArray(['sections' => [], 'order' => []]);

    expect($data->title())->toBe('');
});
test('is empty', function () {
    $empty = PageData::fromArray([]);
    expect($empty->isEmpty())->toBeTrue();

    $notEmpty = PageData::fromArray(sampleData());
    expect($notEmpty->isEmpty())->toBeFalse();
});
test('is not empty', function () {
    $data = PageData::fromArray(sampleData());
    expect($data->isNotEmpty())->toBeTrue();

    $empty = PageData::fromArray([]);
    expect($empty->isNotEmpty())->toBeFalse();
});
test('to array', function () {
    $input = sampleData();
    $data = PageData::fromArray($input);
    $output = $data->toArray();

    expect($output)->toHaveKey('sections');
    expect($output)->toHaveKey('order');
    expect($output['order'])->toBe(['hero', 'footer']);
});
test('to json', function () {
    $data = PageData::fromArray(sampleData());
    $json = $data->toJson();

    $decoded = json_decode($json, true);

    // title and meta are now part of the PageData DTO for the editor
    expect($decoded)->toHaveKey('title');
    expect($decoded)->toHaveKey('meta');
    expect($decoded['title'])->toBe('Home Page');
    expect($decoded['order'])->toBe(['hero', 'footer']);
});
test('json serializable', function () {
    $data = PageData::fromArray(sampleData());
    $json = json_encode($data);

    $decoded = json_decode($json, true);

    // title and meta are now part of the PageData DTO for the editor
    expect($decoded)->toHaveKey('title');
    expect($decoded)->toHaveKey('meta');
    expect($decoded['title'])->toBe('Home Page');
});
test('layout type defaults to page', function () {
    $data = PageData::fromArray(sampleData());

    expect($data->layoutType())->toBe('page');
});
test('layout type returns configured type', function () {
    $data = PageData::fromArray(sampleDataWithLayout());

    expect($data->layoutType())->toBe('page');
});
test('layout type with custom type', function () {
    $input = sampleData();
    $input['layout'] = ['type' => 'landing'];
    $data = PageData::fromArray($input);

    expect($data->layoutType())->toBe('landing');
});
test('layout section returns raw array', function () {
    $data = PageData::fromArray(sampleDataWithLayout());

    $header = $data->layoutSection('header');

    expect($header)->toBeArray();
    expect($header['type'])->toBe('header');
    expect($header['settings']['logo'])->toBe('/img/logo.png');
    expect($header['settings']['menu'])->toBe('menu-1');
    expect($header['settings']['sticky'])->toBeTrue();
});
test('layout section returns null for absent key', function () {
    $data = PageData::fromArray(sampleDataWithLayout());

    expect($data->layoutSection('sidebar'))->toBeNull();
});
test('layout section returns null when disabled', function () {
    $data = PageData::fromArray(sampleDataWithLayout());

    // disabled-section is in the header zone with disabled: true
    expect($data->layoutSection('disabled-section'))->toBeNull();
});
test('layout section normalises missing blocks and order', function () {
    $data = PageData::fromArray(sampleDataWithLayout());

    // The 'footer' section has no 'blocks' or 'order' keys in the fixture
    $footer = $data->layoutSection('footer');

    expect($footer)->toBeArray();
    expect($footer['blocks'])->toBe([]);
    expect($footer['order'])->toBe([]);
});
test('layout section returns null when no layout', function () {
    $data = PageData::fromArray(sampleData());

    expect($data->layoutSection('header'))->toBeNull();
});
test('layout sections returns all normalised', function () {
    $data = PageData::fromArray(sampleDataWithLayout());

    // layoutSections() flattens both zones — header(2) + footer(1) = 3
    $sections = $data->layoutSections();

    expect($sections)->toHaveCount(3);
    expect($sections)->toHaveKey('header');
    expect($sections)->toHaveKey('footer');
    expect($sections)->toHaveKey('disabled-section');

    foreach ($sections as $section) {
        expect($section)->toHaveKey('blocks');
        expect($section)->toHaveKey('order');
    }
});
test('layout sections returns empty when no layout', function () {
    $data = PageData::fromArray(sampleData());

    expect($data->layoutSections())->toBe([]);
});
test('to array includes layout', function () {
    $data = PageData::fromArray(sampleDataWithLayout());
    $output = $data->toArray();

    expect($output)->toHaveKey('layout');
    expect($output['layout']['type'])->toBe('page');

    // header zone has 2 sections (header + disabled-section)
    expect($output['layout'])->toHaveKey('header');
    expect($output['layout'])->toHaveKey('footer');
    expect($output['layout']['header']['sections'])->toHaveCount(2);
    expect($output['layout']['footer']['sections'])->toHaveCount(1);
});
test('to array includes default layout when absent', function () {
    // No layout stored — toArray() returns empty layout
    $data = PageData::fromArray(sampleData());
    $output = $data->toArray();

    expect($output)->toHaveKey('layout');
    expect($output['layout'])->toBe([]);
});
test('to json includes layout', function () {
    $data = PageData::fromArray(sampleDataWithLayout());
    $json = $data->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('layout');
    expect($decoded['layout']['type'])->toBe('page');
    expect($decoded['layout'])->toHaveKey('header');
    expect($decoded['layout'])->toHaveKey('footer');

    // Total sections across both zones: 2 header + 1 footer
    expect($decoded['layout']['header']['sections'])->toHaveCount(2);
    expect($decoded['layout']['footer']['sections'])->toHaveCount(1);
});
test('layout header zone', function () {
    $data = PageData::fromArray(sampleDataWithLayout());
    $header = $data->layoutHeader();

    expect($header)->toHaveKey('sections');
    expect($header)->toHaveKey('order');
    expect($header['sections'])->toHaveCount(2);
    expect($header['order'])->toBe(['header', 'disabled-section']);
});
test('layout footer zone', function () {
    $data = PageData::fromArray(sampleDataWithLayout());
    $footer = $data->layoutFooter();

    expect($footer)->toHaveKey('sections');
    expect($footer)->toHaveKey('order');
    expect($footer['sections'])->toHaveCount(1);
    expect($footer['order'])->toBe(['footer']);
});
test('layout section with blocks', function () {
    $input = sampleData();
    $input['layout'] = [
        'type' => 'page',
        'header' => [
            'sections' => [
                'header' => [
                    'type' => 'header',
                    'disabled' => false,
                    'settings' => ['logo' => '/logo.png'],
                    'blocks' => [
                        'nav-row' => [
                            'type' => 'row',
                            'settings' => ['columns' => '3'],
                            'blocks' => [],
                            'order' => [],
                        ],
                    ],
                    'order' => ['nav-row'],
                ],
            ],
            'order' => ['header'],
        ],
        'footer' => ['sections' => [], 'order' => []],
    ];

    $data = PageData::fromArray($input);
    $header = $data->layoutSection('header');

    expect($header['blocks'])->toHaveCount(1);
    expect($header['blocks'])->toHaveKey('nav-row');
    expect($header['order'])->toBe(['nav-row']);
});
test('wrapper defaults to null', function () {
    $data = PageData::fromArray(sampleData());

    expect($data->wrapper())->toBeNull();
});
test('from array reads wrapper', function () {
    $data = PageData::fromArray([
        ...sampleData(),
        'wrapper' => 'main#content.container',
    ]);

    expect($data->wrapper())->toBe('main#content.container');
});
test('from array ignores empty wrapper string', function () {
    $data = PageData::fromArray([
        ...sampleData(),
        'wrapper' => '',
    ]);

    expect($data->wrapper())->toBeNull();
});
test('from array ignores non string wrapper', function () {
    $data = PageData::fromArray([
        ...sampleData(),
        'wrapper' => 123,
    ]);

    expect($data->wrapper())->toBeNull();
});
test('to array includes wrapper when set', function () {
    $data = PageData::fromArray([
        ...sampleData(),
        'wrapper' => 'div#main',
    ]);

    $array = $data->toArray();
    expect($array)->toHaveKey('wrapper');
    expect($array['wrapper'])->toBe('div#main');
});
test('to array omits wrapper when null', function () {
    $data = PageData::fromArray(sampleData());

    $array = $data->toArray();
    $this->assertArrayNotHasKey('wrapper', $array);
});
