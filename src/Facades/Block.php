<?php

declare(strict_types=1);

namespace PageBuilder\Facades;

use Illuminate\Support\Facades\Facade;
use PageBuilder\Registry\BlockRegistry;

/**
 * @method static void add(string|array $paths)
 * @method static void register(string $type, \PageBuilder\Schema\BlockSchema $schema, string|null $view = null)
 * @method static array|null get(string|null $type = null)
 * @method static bool has(string $type)
 * @method static array types()
 *
 * @see BlockRegistry
 */
class Block extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BlockRegistry::class;
    }
}
