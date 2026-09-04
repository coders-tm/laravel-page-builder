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
