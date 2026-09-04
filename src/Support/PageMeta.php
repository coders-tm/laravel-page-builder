<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Support;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Value Object representing SEO metadata for a page.
 *
 * @implements ArrayAccess<string, string|null>
 * @implements Arrayable<string, string|null>
 */
final class PageMeta implements Arrayable, ArrayAccess, JsonSerializable
{
    public function __construct(
        private readonly ?string $title = null,
        private readonly ?string $metaTitle = null,
        private readonly ?string $metaDescription = null,
        private readonly ?string $metaKeywords = null,
    ) {}

    /**
     * Instantiate from raw array structure.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: isset($data['title']) && is_string($data['title']) && $data['title'] !== '' ? $data['title'] : null,
            metaTitle: isset($data['meta_title']) && is_string($data['meta_title']) && $data['meta_title'] !== '' ? $data['meta_title'] : null,
            metaDescription: isset($data['meta_description']) && is_string($data['meta_description']) && $data['meta_description'] !== '' ? $data['meta_description'] : null,
            metaKeywords: isset($data['meta_keywords']) && is_string($data['meta_keywords']) && $data['meta_keywords'] !== '' ? $data['meta_keywords'] : null,
        );
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function metaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function metaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function metaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function isEmpty(): bool
    {
        return $this->title === null
            && $this->metaTitle === null
            && $this->metaDescription === null
            && $this->metaKeywords === null;
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Convert to raw associative array shape.
     *
     * @return array{title: ?string, meta_title: ?string, meta_description: ?string, meta_keywords: ?string}
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'meta_keywords' => $this->metaKeywords,
        ], fn ($v) => $v !== null);
    }

    /**
     * @return array{title: ?string, meta_title: ?string, meta_description: ?string, meta_keywords: ?string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! is_string($offset)) {
            return null;
        }

        return match ($offset) {
            'title' => $this->title,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'meta_keywords' => $this->metaKeywords,
            default => null,
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Readonly Value Object — immutable
    }

    public function offsetUnset(mixed $offset): void
    {
        // Readonly Value Object — immutable
    }
}
