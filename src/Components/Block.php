<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public const DEFAULT_TYPE = 'block';

    protected function defaultType(): string
    {
        return self::DEFAULT_TYPE;
    }

    public function editorAttributes(): string
    {
        return EditorAttributes::forBlock($this);
    }
}
