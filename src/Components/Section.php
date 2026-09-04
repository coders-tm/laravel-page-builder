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
