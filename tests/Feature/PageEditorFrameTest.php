<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Http\Request;
use Illuminate\View\View;
use PageBuilder\Facades\Page;

test('renders editor frame when editor param present', function () {
    $request = Request::create('/home', 'GET', ['editor' => 'true']);
    $this->app->instance('request', $request);

    $response = Page::render('home', [], true);

    expect($response)->toBeInstanceOf(View::class);
    expect($response->name())->toEqual('pagebuilder::layout');
    expect($response->getData()['config']['basePath'])->toEqual('/');
});
