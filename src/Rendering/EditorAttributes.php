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

namespace PageBuilder\Rendering;

use PageBuilder\Components\Block;
use PageBuilder\Components\Section;
use PageBuilder\PageBuilder;

/**
 * Generates HTML data-attributes for the page builder editor.
 *
 * This is an optional layer — when the editor is not active, all
 * methods return empty strings. The editor uses these attributes
 * to identify and interact with sections and blocks in the DOM.
 */
class EditorAttributes
{
    /**
     * Get editor attributes for a section.
     */
    public static function forSection(Section $section): string
    {
        if (! PageBuilder::editor()) {
            return '';
        }

        $meta = json_encode(array_filter([
            'id' => $section->id,
            'type' => $section->type,
            'name' => $section->name ?: null,
            'disabled' => $section->disabled ?: null,
        ], fn ($v) => $v !== null), JSON_HEX_APOS | JSON_HEX_QUOT);

        $disabledAttr = $section->disabled ? ' pb-disabled-section' : '';

        return trim(sprintf(
            'data-editor-section=\'%s\' data-section-id="%s"%s',
            $meta,
            htmlspecialchars($section->id, ENT_QUOTES),
            $disabledAttr,
        ));
    }

    /**
     * Get editor attributes for a block.
     */
    public static function forBlock(Block $block): string
    {
        if (! PageBuilder::editor()) {
            return '';
        }

        $meta = json_encode(array_filter([
            'id' => $block->id,
            'domId' => $block->id,
            'type' => $block->type,
            'name' => $block->name ?: null,
            'disabled' => $block->disabled ?: null,
        ], fn ($v) => $v !== null), JSON_HEX_APOS | JSON_HEX_QUOT);

        $disabledAttr = $block->disabled ? ' pb-disabled-block' : '';

        return trim(sprintf(
            'data-block-id="%s" data-editor-block=\'%s\'%s',
            htmlspecialchars($block->id, ENT_QUOTES),
            $meta,
            $disabledAttr,
        ));
    }

    /**
     * Auto-inject data-live-text-setting attributes into rendered HTML for
     * all section and block settings that have string values.
     */
    public static function autoInjectLiveText(string $html, Section $section): string
    {
        // Section-level settings
        foreach ($section->settings->all() as $key => $value) {
            if (is_string($value)) {
                $html = static::injectDataLiveText($html, $value, "{$section->id}.{$key}");
            }
        }

        // Block-level settings
        return static::injectBlocksLiveText($html, $section->blocks);
    }

    /**
     * Auto-inject data-image-setting attributes into rendered HTML for
     * section and block settings that contain image URLs or paths.
     */
    public static function autoInjectImageSettings(string $html, Section $section): string
    {
        // Section-level settings
        foreach ($section->settings->all() as $key => $value) {
            if (is_string($value)) {
                $html = static::injectDataImageSetting($html, $value, "{$section->id}.{$key}");
            }
        }

        // Block-level settings
        return static::injectBlocksImageSettings($html, $section->blocks);
    }

    /** Recursively inject image settings for blocks and their children. */
    protected static function injectBlocksImageSettings(string $html, mixed $blocks): string
    {
        foreach ($blocks as $blockId => $block) {
            foreach ($block->settings->all() as $key => $value) {
                if (is_string($value)) {
                    $html = static::injectDataImageSetting($html, $value, "{$blockId}.{$key}");
                }
            }

            if ($block->blocks && count($block->blocks) > 0) {
                $html = static::injectBlocksImageSettings($html, $block->blocks);
            }
        }

        return $html;
    }

    /** Inject a data-image-setting attribute for a specific image setting value into matching <img> tags. */
    public static function injectDataImageSetting(string $html, string $imageVal, string $path): string
    {
        $imageVal = trim($imageVal);
        if (strlen($imageVal) < 2) {
            return $html;
        }

        $parsedPath = parse_url($imageVal, PHP_URL_PATH);
        $urlPath = is_string($parsedPath) ? $parsedPath : $imageVal;
        $basename = basename($urlPath);
        $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif'];
        $isImageExt = in_array($extension, $imageExtensions, true);
        $isImagePath = str_contains($imageVal, '/') || str_contains($imageVal, 'statics') || str_contains($imageVal, 'assets') || str_contains($imageVal, 'storage') || str_contains($imageVal, 'public');

        if (! $isImageExt && ! $isImagePath) {
            return $html;
        }

        $searchTerm = (! empty($basename) && strlen($basename) > 2) ? $basename : $imageVal;
        $escapedSearch = preg_quote($searchTerm, '/');

        $pattern = '/<img\b(?![^>]*\bdata-image-setting=)([^>]*\bsrc=["\'][^"\']*'.$escapedSearch.'[^"\']*["\'][^>]*)>/iu';

        $replaced = preg_replace_callback($pattern, function ($matches) use ($path) {
            return '<img data-image-setting="'.htmlspecialchars($path, ENT_QUOTES).'"'.$matches[1].'>';
        }, $html, 1, $count);

        if ($count > 0 && $replaced !== null) {
            return $replaced;
        }

        return $html;
    }

    /** Recursively inject live text settings for blocks and their children. */
    protected static function injectBlocksLiveText(string $html, mixed $blocks): string
    {
        foreach ($blocks as $blockId => $block) {
            // Inject this block's settings
            foreach ($block->settings->all() as $key => $value) {
                if (is_string($value)) {
                    $html = static::injectDataLiveText($html, $value, "{$blockId}.{$key}");
                }
            }

            // Recurse into nested blocks (e.g. Row -> Column -> Title)
            if ($block->blocks && count($block->blocks) > 0) {
                $html = static::injectBlocksLiveText($html, $block->blocks);
            }
        }

        return $html;
    }

    /** Inject a data-live-text-setting attribute for a specific text value. */
    protected static function injectDataLiveText(string $html, string $text, string $path): string
    {
        $text = trim($text);

        if (strlen($text) < 2) {
            return $html;
        }

        $escapedText = preg_quote($text, '/');

        // Pattern 1: Exact match of inner HTML
        // We look for a tag that DOES NOT already have data-live-text-setting
        $pattern1 = '/(<(?![^>]*data-live-text-setting=)[^>]+)>(\s*'.$escapedText.'\s*<\/[a-zA-Z0-9]+>)/iu';
        $replaced = preg_replace($pattern1, '$1 data-live-text-setting="'.$path.'">$2', $html, 1, $count);

        if ($count > 0 && $replaced !== null) {
            return $replaced;
        }

        // Pattern 2: Text inside a tag without nested tags
        if (strpos($text, '<') === false) {
            $pattern2 = '/(<(?![^>]*data-live-text-setting=)[^>]+)>([^<]*?\b'.$escapedText.'\b[^>]*?<\/[a-zA-Z0-9]+>)/iu';
            $replaced = preg_replace($pattern2, '$1 data-live-text-setting="'.$path.'">$2', $html, 1, $count);

            if ($count > 0 && $replaced !== null) {
                return $replaced;
            }
        }

        return $html;
    }
}
