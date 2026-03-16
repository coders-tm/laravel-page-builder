<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Unit\Support;

use Coderstm\PageBuilder\Support\TemplateVariableResolver;
use Coderstm\PageBuilder\Tests\TestCase;

class TemplateVariableResolverTest extends TestCase
{
    private TemplateVariableResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new TemplateVariableResolver;
    }

    // ── Basic resolution ─────────────────────────────────────────────────────

    public function test_resolves_page_title(): void
    {
        $page = $this->makePage(['title' => 'My Page Title']);

        $data = $this->resolver->resolve(['heading' => '{{ $page->title }}'], $page);

        $this->assertSame('My Page Title', $data['heading']);
    }

    public function test_resolves_without_spaces(): void
    {
        $page = $this->makePage(['title' => 'Compact']);

        $data = $this->resolver->resolve(['heading' => '{{$page->title}}'], $page);

        $this->assertSame('Compact', $data['heading']);
    }

    public function test_resolves_multiple_placeholders_in_one_string(): void
    {
        $page = $this->makePage(['title' => 'Hello', 'meta_title' => 'World']);

        $data = $this->resolver->resolve(
            ['text' => '{{ $page->title }} – {{ $page->meta_title }}'],
            $page,
        );

        $this->assertSame('Hello – World', $data['text']);
    }

    public function test_resolves_multiple_fields(): void
    {
        $page = $this->makePage([
            'title' => 'My Title',
            'meta_description' => 'My Description',
        ]);

        $data = $this->resolver->resolve([
            'title' => '{{ $page->title }}',
            'desc' => '{{ $page->meta_description }}',
        ], $page);

        $this->assertSame('My Title', $data['title']);
        $this->assertSame('My Description', $data['desc']);
    }

    // ── Nested array resolution ───────────────────────────────────────────────

    public function test_resolves_in_nested_arrays(): void
    {
        $page = $this->makePage(['title' => 'Nested Title']);

        $data = $this->resolver->resolve([
            'sections' => [
                'hero' => [
                    'settings' => [
                        'heading' => '{{ $page->title }}',
                    ],
                ],
            ],
        ], $page);

        $this->assertSame('Nested Title', $data['sections']['hero']['settings']['heading']);
    }

    // ── Null page ────────────────────────────────────────────────────────────

    public function test_null_page_removes_placeholders(): void
    {
        $data = $this->resolver->resolve(['heading' => '{{ $page->title }}'], null);

        $this->assertSame('', $data['heading']);
    }

    public function test_null_page_with_surrounding_text(): void
    {
        $data = $this->resolver->resolve(['heading' => 'Hello {{ $page->title }}!'], null);

        $this->assertSame('Hello !', $data['heading']);
    }

    // ── Non-matching strings ─────────────────────────────────────────────────

    public function test_ignores_strings_without_placeholder(): void
    {
        $page = $this->makePage(['title' => 'Test']);

        $data = $this->resolver->resolve(['heading' => 'Static value'], $page);

        $this->assertSame('Static value', $data['heading']);
    }

    public function test_ignores_non_page_placeholders(): void
    {
        $page = $this->makePage(['title' => 'Test']);

        $data = $this->resolver->resolve(['heading' => '{{ $other->title }}'], $page);

        // Non-page placeholder is left as-is (not matched by the pattern)
        $this->assertSame('{{ $other->title }}', $data['heading']);
    }

    public function test_non_string_values_are_preserved(): void
    {
        $page = $this->makePage(['title' => 'Test']);

        $data = $this->resolver->resolve([
            'count' => 42,
            'active' => true,
            'items' => null,
        ], $page);

        $this->assertSame(42, $data['count']);
        $this->assertTrue($data['active']);
        $this->assertNull($data['items']);
    }

    // ── Missing attribute ────────────────────────────────────────────────────

    public function test_resolves_to_empty_string_for_missing_attribute(): void
    {
        $page = $this->makePage([]);

        $data = $this->resolver->resolve(['heading' => '{{ $page->nonexistent_attr }}'], $page);

        $this->assertSame('', $data['heading']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build a simple object whose properties are accessible via $obj->key.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makePage(array $attributes): object
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
}
