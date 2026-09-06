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

test('pagebuilder:install command runs successfully', function () {
    $this->artisan('pagebuilder:install')
        ->expectsOutputToContain('Installing Page Builder...')
        ->expectsOutputToContain('Page Builder installed successfully.')
        ->assertExitCode(Command::SUCCESS);
});
