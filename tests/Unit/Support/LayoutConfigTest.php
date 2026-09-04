<?php

declare(strict_types=1);

use PageBuilder\Support\LayoutConfig;

test('instantiates from empty array with default zone structure', function () {
    $config = LayoutConfig::fromArray([]);

    expect($config->header())->toBe(['sections' => [], 'order' => []]);
    expect($config->footer())->toBe(['sections' => [], 'order' => []]);
    expect($config->headerSections())->toBe([]);
    expect($config->headerOrder())->toBe([]);
});

test('instantiates from raw layout array', function () {
    $raw = [
        'header' => [
            'sections' => ['nav' => ['type' => 'navbar']],
            'order' => ['nav'],
        ],
        'footer' => [
            'sections' => ['copy' => ['type' => 'copyright']],
            'order' => ['copy'],
        ],
    ];

    $config = LayoutConfig::fromArray($raw);

    expect($config->headerSections())->toHaveKey('nav');
    expect($config->headerOrder())->toBe(['nav']);
    expect($config->footerSections())->toHaveKey('copy');
    expect($config->footerOrder())->toBe(['copy']);
    expect($config->toArray())->toBe($raw);
    expect(json_encode($config))->toBe(json_encode($raw));
});
