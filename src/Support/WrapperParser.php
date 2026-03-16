<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Support;

/**
 * Parses a CSS-selector-like wrapper string into an HTML element.
 *
 * Supported format: tag#id.class1.class2[attr1=val1][attr2=val2]
 *
 * Supported tags: <div>, <main>, <section>
 *
 * Examples:
 *   "div#main.container[data-page=1]"
 *   → <div id="main" class="container" data-page="1">…</div>
 *
 *   "main#page-content.wrapper"
 *   → <main id="page-content" class="wrapper">…</main>
 */
final class WrapperParser
{
    private const ALLOWED_TAGS = ['div', 'main', 'section'];

    /**
     * Wrap $content in the HTML element described by $wrapper.
     */
    public function render(string $wrapper, string $content): string
    {
        ['tag' => $tag, 'attributes' => $attributes] = $this->parse($wrapper);
        $attrString = $this->buildAttributeString($attributes);

        return "<{$tag}{$attrString}>{$content}</{$tag}>";
    }

    /**
     * Parse a wrapper string into its tag and attributes.
     *
     * @return array{tag: string, attributes: array<string, string>}
     */
    public function parse(string $wrapper): array
    {
        // Extract tag name (default: div)
        preg_match('/^([a-z][a-z0-9]*)/', $wrapper, $tagMatch);
        $rawTag = strtolower($tagMatch[1] ?? 'div');
        $tag = in_array($rawTag, self::ALLOWED_TAGS, true) ? $rawTag : 'div';

        $attributes = [];

        // Extract id: #identifier
        preg_match('/#([^.#\[\s]+)/', $wrapper, $idMatch);
        if (! empty($idMatch[1])) {
            $attributes['id'] = $idMatch[1];
        }

        // Extract classes: .classname (multiple allowed)
        preg_match_all('/\.([^.#\[\s]+)/', $wrapper, $classMatches);
        if (! empty($classMatches[1])) {
            $attributes['class'] = implode(' ', $classMatches[1]);
        }

        // Extract custom attributes: [key=value]
        preg_match_all('/\[([^\]=\s]+)=([^\]]*)\]/', $wrapper, $attrMatches, PREG_SET_ORDER);
        foreach ($attrMatches as $match) {
            $attributes[$match[1]] = $match[2];
        }

        return ['tag' => $tag, 'attributes' => $attributes];
    }

    /**
     * Build an HTML attribute string from a key–value map.
     */
    private function buildAttributeString(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $parts = [];

        foreach ($attributes as $key => $value) {
            $parts[] = $key.'="'.htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
        }

        return ' '.implode(' ', $parts);
    }
}
