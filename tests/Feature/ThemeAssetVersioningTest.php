<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->publicBase = public_path('themes/test-theme');
    File::makeDirectory("{$this->publicBase}/css", 0755, true);
});
afterEach(function () {
    if (File::exists(public_path('themes'))) {
        File::deleteDirectory(public_path('themes'));
    }

});
test('appends version query param when file exists', function () {
    $file = "{$this->publicBase}/css/app.css";
    File::put($file, 'body { color: #000; }');

    // Ensure a stable mtime we can assert against
    $mtime = time() - 1234;
    @touch($file, $mtime);
    $url = theme('css/app.css', 'test-theme');

    expect($url)->toBeString();

    $matched = preg_match('/[?&]v=(\d+)/', $url, $matches);
    expect($matched)->toBe(1, 'Expected version query param in URL: '.$url);
    expect($matches[1])->toEqual((string) $mtime);
});
