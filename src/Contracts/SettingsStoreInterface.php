<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Contracts;

interface SettingsStoreInterface
{
    /**
     * Get settings data for a section under `_pagebuilder` (e.g., "theme", "layouts").
     */
    public function get(string $section, ?string $key = null, mixed $default = null): mixed;

    /**
     * Set data for a section or specific key within a section.
     *
     * @param  array<string, mixed>|mixed  $value
     */
    public function set(string $section, array|string $key, mixed $value = null): bool;

    /**
     * Delete/forget a section or key within a section.
     */
    public function forget(string $section, ?string $key = null): bool;

    /**
     * Return all settings data under the root `_pagebuilder` key.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Invalidate in-memory cache.
     */
    public function flush(): void;

    /**
     * Get the resolved file path to the settings storage JSON file.
     */
    public function valuesPath(): string;
}
