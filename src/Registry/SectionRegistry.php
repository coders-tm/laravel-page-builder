<?php

declare(strict_types=1);

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
