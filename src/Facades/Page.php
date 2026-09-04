<?php

namespace PageBuilder\Facades;

use Illuminate\Support\Facades\Facade;
use PageBuilder\Services\PageService;

/**
 * @method static array resolve(string $slug, ?\Illuminate\Database\Eloquent\Model $dbPage = null)
 * @method static mixed render(string $slug, array $meta = [])
 * @method static mixed share(mixed $key, mixed $value = null)
 * @method static void routes()
 * @method static array allActive()
 * @method static \Illuminate\Database\Eloquent\Model|null findBySlug(string $slug)
 * @method static bool saveMeta(string $slug, array $meta)
 *
 * @see PageService
 */
class Page extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return PageService::class;
    }
}
