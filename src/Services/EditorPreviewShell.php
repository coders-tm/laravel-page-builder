<?php

declare(strict_types=1);

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
