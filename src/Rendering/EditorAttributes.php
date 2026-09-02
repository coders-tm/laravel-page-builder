<?php

declare(strict_types=1);

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

        return trim(sprintf(
            'data-editor-section=\'%s\' data-section-id="%s"',
            $meta,
            htmlspecialchars($section->id, ENT_QUOTES),
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

        return trim(sprintf(
            'data-block-id="%s" data-editor-block=\'%s\'',
            htmlspecialchars($block->id, ENT_QUOTES),
            $meta,
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
