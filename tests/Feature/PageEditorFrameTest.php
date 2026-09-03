<?php

declare(strict_types=1);

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
