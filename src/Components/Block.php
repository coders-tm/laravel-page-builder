<?php

declare(strict_types=1);

namespace PageBuilder\Components;

use PageBuilder\Rendering\EditorAttributes;

/**
 * Runtime block instance hydrated from page JSON.
 *
 * Blade usage:
 *
 *   <div {{ $block->editorAttributes() }}>
 *     <h3>{{ $block->settings->heading }}</h3>
 *
 *     @blocks($block)
 *   </div>
 */
class Block extends BaseComponent
{
    protected function defaultType(): string
    {
        return 'block';
    }

    public function editorAttributes(): string
    {
        return EditorAttributes::forBlock($this);
    }
}
