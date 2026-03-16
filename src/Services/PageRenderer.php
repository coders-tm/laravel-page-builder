<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Services;

use Coderstm\PageBuilder\Rendering\Renderer;
use Coderstm\PageBuilder\Support\PageData;
use Coderstm\PageBuilder\Support\WrapperParser;

/**
 * Responsible exclusively for rendering page data into HTML.
 *
 * Storage (load/save) is handled by PageStorage.
 */
class PageRenderer
{
    public function __construct(
        protected readonly Renderer $renderer,
        protected readonly PageStorage $storage,
        protected readonly WrapperParser $wrapperParser,
    ) {}

    /**
     * Render a page by slug, returning the full HTML string or null when not found.
     */
    public function render(string $slug, bool $editor = false): ?string
    {
        $page = $this->storage->load($slug);

        if ($page === null) {
            return null;
        }

        return $this->renderPage($page, $editor);
    }

    /**
     * Render a PageData (or raw array) into concatenated section HTML.
     *
     * Disabled sections are always omitted — the renderer never surfaces them,
     * regardless of whether editor mode is active.
     */
    public function renderPage(array|PageData $page, bool $editor = false): string
    {
        $pageData = $page instanceof PageData ? $page : PageData::fromArray($page);

        $html = '';

        foreach ($pageData->order() as $sectionId) {
            $sectionData = $pageData->section($sectionId);

            if ($sectionData === null) {
                continue;
            }

            if (! empty($sectionData['disabled'])) {
                continue;
            }

            $html .= $this->renderer->renderRawSection($sectionId, $sectionData, $editor);
        }

        if ($wrapper = $pageData->wrapper()) {
            $html = $this->wrapperParser->render($wrapper, $html);
        }

        return $html;
    }
}
