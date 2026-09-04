<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Services;

use PageBuilder\Rendering\Renderer;
use PageBuilder\Support\PageData;
use PageBuilder\Support\WrapperParser;

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

            $html .= $this->renderer->renderRawSection($sectionId, $sectionData, $editor, ['__pb_page' => $pageData]);
        }

        if ($wrapper = $pageData->wrapper()) {
            $html = $this->wrapperParser->render($wrapper, $html);
        }

        return $html;
    }
}
