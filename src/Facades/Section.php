<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Facades;

use Illuminate\Support\Facades\Facade;
use PageBuilder\Registry\SectionRegistry;

/**
 * @method static void add(string|array $paths)
 * @method static void register(string $type, \PageBuilder\Schema\SectionSchema $schema, string|null $view = null)
 * @method static array|null get(string|null $type = null)
 * @method static bool has(string $type)
 * @method static array types()
 *
 * @see SectionRegistry
 */
class Section extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SectionRegistry::class;
    }
}
