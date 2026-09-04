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

use Illuminate\Foundation\Testing\RefreshDatabase;
use PageBuilder\Collections\BlockCollection;
use PageBuilder\Components\Block;
use PageBuilder\Components\Section;
use PageBuilder\Components\Settings;
use PageBuilder\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');
uses(RefreshDatabase::class)->in('Feature');

function makeBlock(string $id, string $type = 'block'): Block
{
    return new Block([
        'id' => $id,
        'type' => $type,
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);
}

function makeSection(string $id, string $type = 'section', bool $disabled = false): Section
{
    return new Section([
        'id' => $id,
        'type' => $type,
        'disabled' => $disabled,
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);
}

function makeEditorSection(string $id = 'hero-1', string $type = 'hero'): Section
{
    return new Section([
        'id' => $id,
        'type' => $type,
        'name' => 'Hero',
        'settings' => new Settings(['title' => 'Hello'], []),
        'blocks' => new BlockCollection,
    ]);
}

function makeEditorBlock(string $id = 'block-1', string $type = 'row'): Block
{
    return new Block([
        'id' => $id,
        'type' => $type,
        'name' => 'Row',
        'settings' => new Settings([], []),
        'blocks' => new BlockCollection,
    ]);
}
