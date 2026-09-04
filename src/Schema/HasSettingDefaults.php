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

namespace PageBuilder\Schema;

/**
 * Provides a shared settingDefaults() implementation for schema classes
 * that hold an array of SettingSchema objects.
 */
trait HasSettingDefaults
{
    /**
     * Extract default values from a settings array, keyed by setting id.
     *
     * @param  array<int, SettingSchema>  $settings
     * @return array<string, mixed>
     */
    private static function extractSettingDefaults(array $settings): array
    {
        $defaults = [];

        foreach ($settings as $setting) {
            if ($setting->id !== null && $setting->id !== '') {
                $defaults[$setting->id] = $setting->default;
            }
        }

        return $defaults;
    }
}
