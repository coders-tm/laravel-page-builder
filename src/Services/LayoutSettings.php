<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Services;

use PageBuilder\Contracts\SettingsStoreInterface;
use PageBuilder\Registry\LayoutParser;
use PageBuilder\Support\LayoutConfig;

/**
 * Manages shared layout configurations stored in settings.json under `_pagebuilder.layouts`.
 *
 * Each layout definition contains header and footer zones with sections and ordering.
 * Delegates storage file I/O to SettingsStoreInterface and supports LayoutConfig DTOs.
 */
class LayoutSettings
{
    public const STORAGE_SECTION = 'layouts';

    protected SettingsStoreInterface $store;

    public function __construct(?SettingsStoreInterface $store = null)
    {
        $this->store = $store ?? app(SettingsStoreInterface::class);
    }

    /**
     * Get all shared layout configurations.
     *
     * @return array<string, array{header: array, footer: array}>
     */
    public function all(): array
    {
        $layouts = $this->store->get(self::STORAGE_SECTION);

        return is_array($layouts) ? $layouts : [];
    }

    /**
     * Check whether a specific layout configuration exists.
     */
    public function has(string $layoutType): bool
    {
        return array_key_exists($layoutType, $this->all());
    }

    /**
     * Get a specific layout configuration array by layout type.
     *
     * @return array{header: array, footer: array}|array{}
     */
    public function get(string $layoutType): array
    {
        $all = $this->all();

        return $all[$layoutType] ?? [];
    }

    /**
     * Get a specific layout configuration as a type-safe LayoutConfig DTO.
     */
    public function getConfig(string $layoutType): LayoutConfig
    {
        return LayoutConfig::fromArray($this->get($layoutType));
    }

    /**
     * Save or overwrite a layout configuration for the given layout type.
     *
     * Flushes the LayoutParser cache after saving to ensure changes take immediate effect.
     *
     * @param  array<string, mixed>|LayoutConfig  $config
     */
    public function save(string $layoutType, array|LayoutConfig $config): bool
    {
        $data = $config instanceof LayoutConfig ? $config->toArray() : $config;

        $result = $this->store->set(self::STORAGE_SECTION, $layoutType, $data);

        if ($result) {
            app(LayoutParser::class)->flush();
        }

        return $result;
    }

    /**
     * Delete a specific layout configuration by layout type.
     *
     * Flushes the LayoutParser cache after removal.
     */
    public function delete(string $layoutType): bool
    {
        $result = $this->store->forget(self::STORAGE_SECTION, $layoutType);

        if ($result) {
            app(LayoutParser::class)->flush();
        }

        return $result;
    }

    /**
     * Invalidate in-memory cached layout data.
     */
    public function flush(): void
    {
        $this->store->flush();
    }
}
