<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Support;

/**
 * Resolves `{{ $page->attribute }}` placeholders in template data.
 *
 * Placeholders in section settings (and any nested string values) are
 * replaced with the corresponding attribute from the given page model.
 * When $page is null, placeholders are replaced with an empty string.
 *
 * Supported syntax (whitespace around the expression is ignored):
 *   {{ $page->title }}
 *   {{$page->meta_description}}
 */
final class TemplateVariableResolver
{
    /**
     * Recursively resolve all placeholders in $data against $page.
     *
     * @param  array<string, mixed>  $data
     * @param  mixed  $page  Eloquent model or null
     * @return array<string, mixed>
     */
    public function resolve(array $data, mixed $page): array
    {
        return $this->resolveArray($data, $page);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveArray(array $data, mixed $page): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->resolveString($value, $page);
            } elseif (is_array($value)) {
                $data[$key] = $this->resolveArray($value, $page);
            }
        }

        return $data;
    }

    private function resolveString(string $value, mixed $page): string
    {
        return preg_replace_callback(
            '/\{\{\s*\$page->([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            function (array $matches) use ($page): string {
                if ($page === null) {
                    return '';
                }

                return (string) ($page->{$matches[1]} ?? '');
            },
            $value,
        );
    }
}
