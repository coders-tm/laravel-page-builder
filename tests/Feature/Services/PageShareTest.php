<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\View;
use PageBuilder\Facades\Page;
use PageBuilder\Models\Page as PageModel;

test('Page::share shares key and value with views', function () {
    Page::share('custom_key', 'custom_value');

    expect(View::shared('custom_key'))->toBe('custom_value');
});

test('Page::share shares associative array with views', function () {
    Page::share(['foo' => 'bar', 'baz' => 'qux']);

    expect(View::shared('foo'))->toBe('bar');
    expect(View::shared('baz'))->toBe('qux');
});

test('Page::share shares model instance under page variable', function () {
    $model = new PageModel(['title' => 'Test Title']);
    Page::share($model);

    expect(View::shared('page'))->toBe($model);
});

test('Page::share with null model shares null under page variable', function () {
    Page::share(null);

    expect(View::shared('page'))->toBeNull();
});
