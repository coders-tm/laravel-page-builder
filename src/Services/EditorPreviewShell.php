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

namespace PageBuilder\Services;

final class EditorPreviewShell
{
    /**
     * Render the isolated preview shell used by picker iframes (`pb-preview=1`).
     *
     * The shell strips host-page body markup and mounts an isolated
     * `<pb-editor>` root for picker preview HTML injection.
     */
    public function render(): string
    {
        return (string) view('pagebuilder::editor-preview-shell')->render();
    }
}
