<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
