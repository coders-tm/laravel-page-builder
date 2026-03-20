<?php

namespace Coderstm\PageBuilder\Services;

use Coderstm\PageBuilder\Facades\Block;
use Coderstm\PageBuilder\Facades\Section;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Qirolab\Theme\Theme as Base;

class Theme extends Base
{
    /**
     * Get the config of the theme.
     */
    public static function config(?string $theme = null): ?array
    {
        try {
            $theme = $theme ?? self::active();

            if ($theme) {
                $configPath = self::finder()->getThemePath($theme, 'config.json');

                if (File::exists($configPath)) {
                    return json_decode(file_get_contents($configPath), true);
                }
            }

            return null;
        } catch (\Throwable $e) {
            // You could log the exception here for debugging purposes
            return null;
        }
    }

    /**
     * Set the active theme and optionally its parent theme.
     */
    public static function set(string $theme, ?string $parentTheme = null): void
    {
        $config = self::config($theme);

        if (! $parentTheme && isset($config['parent'])) {
            $parentTheme = $config['parent'];
        }

        self::finder()->setActiveTheme($theme, $parentTheme);

        if (! File::exists(self::finder()->getThemePath($theme))) {
            return;
        }

        // Load theme translations from the theme's lang directory
        self::loadTranslations($theme);

        Config::set('pagebuilder.pages', self::path('views/pages', $theme));

        // Add theme sections
        if ($sectionsPath = self::path('views/sections', $theme)) {
            Section::add($sectionsPath);
        }

        // Add theme blocks
        if ($blocksPath = self::path('views/blocks', $theme)) {
            Block::add($blocksPath);
        }

        // Set theme settings configuration
        Config::set('pagebuilder.theme_settings_path', self::path('config.json', $theme));
        Config::set('pagebuilder.cache.prefix', "pagebuilder.page.{$theme}");
    }

    /**
     * Load translations for the specified theme.
     */
    protected static function loadTranslations(string $theme): void
    {
        $langPath = self::path('lang', $theme);

        if ($langPath && File::exists($langPath)) {
            $translator = App::make('translator');

            // Add theme lang path to the translator's main paths (no namespace)
            $translator->addPath($langPath);
        }
    }

    /**
     * Get the path of a specific file within the theme or its parent theme.
     */
    public static function path(?string $path = null, ?string $theme = null): ?string
    {
        $theme = $theme ?? self::active();

        // Try to get the file path from the current theme
        $themePath = self::getThemeFilePath($theme, $path);

        if ($themePath) {
            return $themePath;
        }

        // Fallback to the parent theme if available
        $config = self::config($theme);

        if (isset($config['parent'])) {
            return self::getThemeFilePath($config['parent'], $path);
        }

        return null;
    }

    /**
     * Get the base path of a specific file within the theme or its parent theme.
     */
    public static function basePath(?string $path = null, ?string $theme = null): ?string
    {
        return self::finder()->getThemePath($theme ?? self::active(), $path);
    }

    /**
     * Get the public path of a specific file within the theme or its parent theme.
     */
    public static function publicPath(?string $path = null, ?string $theme = null): ?string
    {
        return self::basePath("public/$path", $theme);
    }

    /**
     * Helper method to get the file path from a specific theme.
     */
    protected static function getThemeFilePath(string $theme, ?string $path = null): ?string
    {
        $themePath = self::finder()->getThemePath($theme, $path);

        return File::exists($themePath) ? $themePath : null;
    }

    public static function mixPath($theme = null)
    {
        $theme = $theme ?? self::active();
        $publicPath = self::basePath('.public', $theme);

        if (is_file($publicPath)) {
            $path = rtrim(file_get_contents($publicPath));
            if (! str_starts_with($path, '/')) {
                $path = "/{$path}";
            }

            return $path;
        }

        return "/themes/$theme";
    }

    public static function assetsPath(string $themeName, ?string $path = null)
    {
        $mixPath = self::mixPath($themeName);

        if (! $path) {
            return public_path($mixPath);
        }

        if (str_starts_with($path, '/')) {
            $path = ltrim($path, '/');
        }

        return public_path("$mixPath/$path");
    }

    /**
     * Get theme's asset url.
     */
    public static function url(string $asset, bool $absolute = true, ?string $theme = null): ?string
    {
        $theme = $theme ?? self::active();
        $mixPath = self::mixPath($theme);

        if (! str_starts_with($asset, '/')) {
            $asset = "/{$asset}";
        }

        $path = $mixPath.$asset;

        return $absolute ? $path : url($path);
    }

    public static function useThemePublic(): bool
    {
        return config('coderstm.theme_public') === true;
    }
}
