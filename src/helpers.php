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

use Illuminate\Support\HtmlString;
use PageBuilder\PageBuilder;
use PageBuilder\Support\Mix;
use PageBuilder\Support\Vite;

if (! function_exists('pb_editor')) {
    /**
     * Check if the page builder editor mode is active.
     */
    function pb_editor(): bool
    {
        return PageBuilder::editor();
    }
}

if (! function_exists('theme')) {
    /**
     * Get the path to a versioned theme's Mix file.
     *
     * @param  string  $path
     * @param  string|null  $themeName
     * @return HtmlString|string
     */
    function theme($path, $themeName = null)
    {
        return (string) app(Mix::class)(...func_get_args());
    }
}

if (! function_exists('theme_vite')) {
    /**
     * Get the HTML tags for a theme's Vite assets.
     *
     * @param  array|string  $entrypoints
     * @param  string|null  $themeName
     * @return HtmlString
     */
    function theme_vite($entrypoints, $themeName = null)
    {
        return (string) app(Vite::class)(...func_get_args());
    }
}
