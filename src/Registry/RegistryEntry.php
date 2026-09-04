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

namespace PageBuilder\Registry;

use PageBuilder\Schema\BlockSchema;
use PageBuilder\Schema\SectionSchema;

/**
 * Typed value object for a single registry entry.
 *
 * Replaces the raw `array{type, view, schema}` previously returned
 * by BaseRegistry::get(), providing compile-time type safety.
 */
final class RegistryEntry
{
    public function __construct(
        public readonly string $type,
        public readonly string $view,
        public readonly SectionSchema|BlockSchema|null $schema = null,
    ) {}

    /**
     * @return array{type: string, view: string, schema: SectionSchema|BlockSchema|null}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'view' => $this->view,
            'schema' => $this->schema,
        ];
    }
}
