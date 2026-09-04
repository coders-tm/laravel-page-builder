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

/**
 * Discovers and provides block schemas from registered Blade file paths.
 *
 * The last registration for a given type wins, allowing themes to
 * shadow built-in blocks.
 *
 * @extends BaseRegistry<BlockSchema>
 */
class BlockRegistry extends BaseRegistry
{
    protected function viewPrefix(): string
    {
        return 'blocks';
    }

    protected function createSchema(string $type, array $rawSchema): BlockSchema
    {
        return new BlockSchema($rawSchema);
    }

    /**
     * Inject the filename-derived type into the raw schema so BlockSchema
     * validation passes even if the Blade file omits 'type'.
     */
    protected function prepareRawSchema(string $type, array $rawSchema): array
    {
        $rawSchema['type'] = $type;

        return $rawSchema;
    }
}
