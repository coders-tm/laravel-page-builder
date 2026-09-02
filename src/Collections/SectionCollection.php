<?php

declare(strict_types=1);

namespace PageBuilder\Collections;

use PageBuilder\Components\Section;
use PageBuilder\Rendering\Renderer;

/**
 * Ordered collection of Section instances for a page.
 *
 * Inherits iteration, filtering, lookup, and serialization from BaseCollection.
 * Adds domain-specific methods: render() and enabled().
 *
 * @extends BaseCollection<Section>
 */
final class SectionCollection extends BaseCollection
{
    /** @param array<string, Section> $orderedSections */
    public function __construct(array $orderedSections = [])
    {
        parent::__construct($orderedSections);
    }

    /**
     * Render all sections in order and return concatenated HTML.
     */
    public function render(): string
    {
        $renderer = app(Renderer::class);

        $html = '';

        foreach ($this->items as $section) {
            $html .= $renderer->renderSection($section);
        }

        return $html;
    }

    /**
     * Return a new collection containing only non-disabled sections.
     */
    public function enabled(): static
    {
        return new self(
            array_filter($this->items, fn (Section $section) => ! $section->disabled)
        );
    }
}
