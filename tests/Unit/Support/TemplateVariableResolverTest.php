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

use PageBuilder\Support\TemplateVariableResolver;

beforeEach(function () {
    $this->resolver = new TemplateVariableResolver;
});
test('resolves page title', function () {
    $page = makePage(['title' => 'My Page Title']);

    $data = $this->resolver->resolve(['heading' => '{{ $page->title }}'], $page);

    expect($data['heading'])->toBe('My Page Title');
});
test('resolves without spaces', function () {
    $page = makePage(['title' => 'Compact']);

    $data = $this->resolver->resolve(['heading' => '{{$page->title}}'], $page);

    expect($data['heading'])->toBe('Compact');
});
test('resolves multiple placeholders in one string', function () {
    $page = makePage(['title' => 'Hello', 'meta_title' => 'World']);

    $data = $this->resolver->resolve(
        ['text' => '{{ $page->title }} – {{ $page->meta_title }}'],
        $page,
    );

    expect($data['text'])->toBe('Hello – World');
});
test('resolves multiple fields', function () {
    $page = makePage([
        'title' => 'My Title',
        'meta_description' => 'My Description',
    ]);

    $data = $this->resolver->resolve([
        'title' => '{{ $page->title }}',
        'desc' => '{{ $page->meta_description }}',
    ], $page);

    expect($data['title'])->toBe('My Title');
    expect($data['desc'])->toBe('My Description');
});
test('resolves in nested arrays', function () {
    $page = makePage(['title' => 'Nested Title']);

    $data = $this->resolver->resolve([
        'sections' => [
            'hero' => [
                'settings' => [
                    'heading' => '{{ $page->title }}',
                ],
            ],
        ],
    ], $page);

    expect($data['sections']['hero']['settings']['heading'])->toBe('Nested Title');
});
test('null page removes placeholders', function () {
    $data = $this->resolver->resolve(['heading' => '{{ $page->title }}'], null);

    expect($data['heading'])->toBe('');
});
test('null page with surrounding text', function () {
    $data = $this->resolver->resolve(['heading' => 'Hello {{ $page->title }}!'], null);

    expect($data['heading'])->toBe('Hello !');
});
test('ignores strings without placeholder', function () {
    $page = makePage(['title' => 'Test']);

    $data = $this->resolver->resolve(['heading' => 'Static value'], $page);

    expect($data['heading'])->toBe('Static value');
});
test('ignores non page placeholders', function () {
    $page = makePage(['title' => 'Test']);

    $data = $this->resolver->resolve(['heading' => '{{ $other->title }}'], $page);

    // Non-page placeholder is left as-is (not matched by the pattern)
    expect($data['heading'])->toBe('{{ $other->title }}');
});
test('non string values are preserved', function () {
    $page = makePage(['title' => 'Test']);

    $data = $this->resolver->resolve([
        'count' => 42,
        'active' => true,
        'items' => null,
    ], $page);

    expect($data['count'])->toBe(42);
    expect($data['active'])->toBeTrue();
    expect($data['items'])->toBeNull();
});
test('resolves to empty string for missing attribute', function () {
    $page = makePage([]);

    $data = $this->resolver->resolve(['heading' => '{{ $page->nonexistent_attr }}'], $page);

    expect($data['heading'])->toBe('');
});
// ── Helpers ──────────────────────────────────────────────────────────────
/**
 * Build a simple object whose properties are accessible via $obj->key.
 *
 * @param  array<string, mixed>  $attributes
 */
function makePage(array $attributes): object
{
    return new class($attributes)
    {
        public function __construct(private array $attributes) {}

        public function __get(string $name): mixed
        {
            return $this->attributes[$name] ?? null;
        }

        public function __isset(string $name): bool
        {
            return isset($this->attributes[$name]);
        }
    };
}
