<?php

use PageBuilder\PageBuilder;
use PageBuilder\Support\Mix;
use PageBuilder\Support\Vite;
use Illuminate\Support\HtmlString;

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
