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

use PageBuilder\Support\WrapperParser;

beforeEach(function () {
    $this->parser = new WrapperParser;
});
test('parses tag only', function () {
    $result = $this->parser->parse('div');

    expect($result['tag'])->toBe('div');
    expect($result['attributes'])->toBe([]);
});
test('parses main tag', function () {
    $result = $this->parser->parse('main');

    expect($result['tag'])->toBe('main');
});
test('parses section tag', function () {
    $result = $this->parser->parse('section');

    expect($result['tag'])->toBe('section');
});
test('falls back to div for disallowed tag', function () {
    $result = $this->parser->parse('article#some-id');

    expect($result['tag'])->toBe('div');
    expect($result['attributes']['id'])->toBe('some-id');
});
test('parses id', function () {
    $result = $this->parser->parse('div#main-content');

    expect($result['tag'])->toBe('div');
    expect($result['attributes']['id'])->toBe('main-content');
});
test('parses single class', function () {
    $result = $this->parser->parse('div.container');

    expect($result['attributes']['class'])->toBe('container');
});
test('parses multiple classes', function () {
    $result = $this->parser->parse('div.container.fluid.mx-auto');

    expect($result['attributes']['class'])->toBe('container fluid mx-auto');
});
test('parses id and class', function () {
    $result = $this->parser->parse('div#div_id.div_class');

    expect($result['attributes']['id'])->toBe('div_id');
    expect($result['attributes']['class'])->toBe('div_class');
});
test('parses custom attribute', function () {
    $result = $this->parser->parse('div[data-page=1]');

    expect($result['attributes']['data-page'])->toBe('1');
});
test('parses multiple attributes', function () {
    $result = $this->parser->parse('div[attr-one=value1][attr-two=value2]');

    expect($result['attributes']['attr-one'])->toBe('value1');
    expect($result['attributes']['attr-two'])->toBe('value2');
});
test('parses full selector from spec example', function () {
    // Example from the spec: div#div_id.div_class[attribute-one=value]
    $result = $this->parser->parse('div#div_id.div_class[attribute-one=value]');

    expect($result['tag'])->toBe('div');
    expect($result['attributes']['id'])->toBe('div_id');
    expect($result['attributes']['class'])->toBe('div_class');
    expect($result['attributes']['attribute-one'])->toBe('value');
});
test('parses main with id', function () {
    $result = $this->parser->parse('main#page-content');

    expect($result['tag'])->toBe('main');
    expect($result['attributes']['id'])->toBe('page-content');
});
test('render wraps content in div', function () {
    $html = $this->parser->render('div', '<p>Hello</p>');

    expect($html)->toBe('<div><p>Hello</p></div>');
});
test('render with id and class', function () {
    $html = $this->parser->render('div#div_id.div_class', 'content');

    expect($html)->toBe('<div id="div_id" class="div_class">content</div>');
});
test('render with custom attribute', function () {
    $html = $this->parser->render('div[attribute-one=value]', 'content');

    expect($html)->toBe('<div attribute-one="value">content</div>');
});
test('render full spec example', function () {
    $html = $this->parser->render('div#div_id.div_class[attribute-one=value]', '<!-- sections -->');

    expect($html)->toBe('<div id="div_id" class="div_class" attribute-one="value"><!-- sections --></div>');
});
test('render main wrapper', function () {
    $html = $this->parser->render('main#page-content.wrapper', '<section>x</section>');

    expect($html)->toBe('<main id="page-content" class="wrapper"><section>x</section></main>');
});
test('render escapes attribute values', function () {
    $html = $this->parser->render('div#id<script>', 'content');

    $this->assertStringNotContainsString('<script>', $html);
});
