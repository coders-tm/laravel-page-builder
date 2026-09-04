<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Contracts;

use PageBuilder\Components\BaseComponent;
use PageBuilder\Components\Block;
use PageBuilder\Components\Section;

/**
 * Contract for the page builder rendering engine.
 *
 * Defines the public API for rendering sections and blocks into HTML,
 * allowing consumers to depend on an abstraction rather than the
 * concrete Renderer implementation.
 */
interface RendererInterface
{
    /**
     * Render a Section object into HTML.
     *
     * @param  array<string, mixed>  $data  Extra view variables (e.g. ['page' => $pageData])
     */
    public function renderSection(Section $section, array $data = []): string;

    /**
     * Render a single Block into HTML.
     * The parent (Section or Block) is passed so views can access context.
     */
    public function renderBlock(Block $block, ?BaseComponent $parent = null): string;

    /**
     * Render all blocks within a section, concatenating the HTML.
     */
    public function renderBlocks(Section $section): string;

    /**
     * Render all child blocks within a Block (e.g. columns inside a row).
     */
    public function renderBlockChildren(Block $block): string;

    /**
     * Hydrate a raw section array (from page JSON) into a typed Section object.
     *
     * @param  array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}  $data
     */
    public function hydrateSection(string $sectionId, array $data, bool $editor = false): Section;

    /**
     * Render a raw section array directly (for API preview calls).
     *
     * @param  array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}  $sectionData
     * @param  array<string, mixed>  $data  Extra view variables (e.g. ['page' => $pageData])
     */
    public function renderRawSection(string $sectionId, array $sectionData, bool $editor = false, array $data = []): string;

    /**
     * Render a raw block array directly (for API preview calls).
     *
     * @param  array{type?: string, settings?: array<string, mixed>, blocks?: array<string, mixed>, order?: list<string>|null, disabled?: bool}  $blockData
     */
    public function renderRawBlock(string $blockId, array $blockData, bool $editor = false): string;
}
