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

namespace PageBuilder\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Value Object representing a structured layout configuration (header and footer zones).
 *
 * @implements Arrayable<string, array{sections: array<string, mixed>, order: array<int, string>}>
 */
final class LayoutConfig implements Arrayable, JsonSerializable
{
    /**
     * @param  array{sections: array<string, mixed>, order: array<int, string>}  $header
     * @param  array{sections: array<string, mixed>, order: array<int, string>}  $footer
     */
    public function __construct(
        private readonly array $header = ['sections' => [], 'order' => []],
        private readonly array $footer = ['sections' => [], 'order' => []],
    ) {}

    /**
     * Instantiate from raw array data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $footer = is_array($data['footer'] ?? null) ? $data['footer'] : [];

        return new self(
            header: [
                'sections' => is_array($header['sections'] ?? null) ? $header['sections'] : [],
                'order' => is_array($header['order'] ?? null) ? array_values($header['order']) : [],
            ],
            footer: [
                'sections' => is_array($footer['sections'] ?? null) ? $footer['sections'] : [],
                'order' => is_array($footer['order'] ?? null) ? array_values($footer['order']) : [],
            ],
        );
    }

    /**
     * Get the header zone structure.
     *
     * @return array{sections: array<string, mixed>, order: array<int, string>}
     */
    public function header(): array
    {
        return $this->header;
    }

    /**
     * Get the footer zone structure.
     *
     * @return array{sections: array<string, mixed>, order: array<int, string>}
     */
    public function footer(): array
    {
        return $this->footer;
    }

    /**
     * Get header sections map.
     *
     * @return array<string, mixed>
     */
    public function headerSections(): array
    {
        return $this->header['sections'] ?? [];
    }

    /**
     * Get footer sections map.
     *
     * @return array<string, mixed>
     */
    public function footerSections(): array
    {
        return $this->footer['sections'] ?? [];
    }

    /**
     * Get header section ordering.
     *
     * @return array<int, string>
     */
    public function headerOrder(): array
    {
        return $this->header['order'] ?? [];
    }

    /**
     * Get footer section ordering.
     *
     * @return array<int, string>
     */
    public function footerOrder(): array
    {
        return $this->footer['order'] ?? [];
    }

    /**
     * Convert to array structure for storage/serialization.
     *
     * @return array{header: array{sections: array<string, mixed>, order: array<int, string>}, footer: array{sections: array<string, mixed>, order: array<int, string>}}
     */
    public function toArray(): array
    {
        return [
            'header' => $this->header,
            'footer' => $this->footer,
        ];
    }

    /**
     * @return array{header: array{sections: array<string, mixed>, order: array<int, string>}, footer: array{sections: array<string, mixed>, order: array<int, string>}}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
