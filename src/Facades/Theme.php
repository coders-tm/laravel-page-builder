<?php

namespace PageBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array|null config(?string $theme = null)
 * @method static void set(string $theme, ?string $parentTheme = null)
 * @method static string|null path(?string $path = null, ?string $theme = null)
 * @method static string|null basePath(?string $path = null, ?string $theme = null)
 * @method static string|null publicPath(?string $path = null, ?string $theme = null)
 * @method static string mixPath($theme = null)
 * @method static string assetsPath(string $themeName, ?string $path = null)
 * @method static string|null url(string $asset, bool $absolute = true, ?string $theme = null)
 * @method static bool useThemePublic()
 * @method static string active()
 *
 * @see \PageBuilder\Services\Theme
 */
class Theme extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'theme';
    }
}
