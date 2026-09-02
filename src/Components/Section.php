<?php

declare(strict_types=1);

namespace PageBuilder\Components;

use PageBuilder\Rendering\EditorAttributes;

/**
 * Runtime section instance hydrated from page JSON.
 *
 * Blade usage:
 *
 *   <section {{ $section->editorAttributes() }}>
 *     <h1>{{ $section->settings->title }}</h1>
 *
 *     @blocks($section)
 *   </section>
 */
class Section extends BaseComponent
{
    public function editorAttributes(): string
    {
        return EditorAttributes::forSection($this);
    }
}
