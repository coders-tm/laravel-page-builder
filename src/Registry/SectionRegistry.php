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

use PageBuilder\Schema\SectionSchema;

/**
 * Discovers and provides section schemas from registered Blade file paths.
 *
 * The last registration for a given type wins, allowing themes to
 * shadow built-in sections.
 *
 * @extends BaseRegistry<SectionSchema>
 */
class SectionRegistry extends BaseRegistry
{
    protected function viewPrefix(): string
    {
        return 'sections';
    }

    protected function createSchema(string $type, array $rawSchema): SectionSchema
    {
        return new SectionSchema($rawSchema);
    }
}
