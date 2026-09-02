<?php

declare(strict_types=1);

namespace PageBuilder\Collections;

use PageBuilder\Components\Block;

/**
 * Ordered collection of Block instances within a section.
 *
 * Inherits iteration, filtering, lookup, and serialization from BaseCollection.
 *
 * @extends BaseCollection<Block>
 */
final class BlockCollection extends BaseCollection
{
    /** @param array<string, Block> $orderedBlocks */
    public function __construct(array $orderedBlocks = [])
    {
        parent::__construct($orderedBlocks);
    }
}
