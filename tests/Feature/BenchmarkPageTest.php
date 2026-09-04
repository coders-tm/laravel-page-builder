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

use Illuminate\Console\Command;

test('pagebuilder:benchmark command fails for missing page slug', function () {
    $this->artisan('pagebuilder:benchmark', ['slug' => 'non-existent-page-slug-12345'])
        ->expectsOutputToContain('Page with slug [non-existent-page-slug-12345] not found.')
        ->assertExitCode(Command::FAILURE);
});

test('pagebuilder:benchmark command runs successfully for workbench benchmark page', function () {
    $this->artisan('pagebuilder:benchmark', ['slug' => 'benchmark', '--runs' => 2])
        ->expectsOutputToContain('Benchmarking page builder for slug: [benchmark]')
        ->expectsOutputToContain('Source')
        ->expectsOutputToContain('Sections')
        ->expectsOutputToContain('Blade Rendering')
        ->expectsOutputToContain('Full Pipeline')
        ->assertExitCode(Command::SUCCESS);
});

test('pagebuilder:benchmark command accepts editor flag', function () {
    $this->artisan('pagebuilder:benchmark', [
        'slug' => 'benchmark',
        '--runs' => 1,
        '--editor' => true,
    ])
        ->expectsOutputToContain('Editor Mode')
        ->assertExitCode(Command::SUCCESS);
});
