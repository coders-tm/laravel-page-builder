<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Registry;

use PageBuilder\Registry\LayoutParser;
use PageBuilder\Tests\TestCase;

class LayoutParserTest extends TestCase
{
    private LayoutParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = $this->app->make(LayoutParser::class);
    }

    public function test_extract_zoned_keys(): void
    {
        $keys = $this->parser->extractZonedKeys('page');

        $this->assertSame(['header'], $keys['header']);
        $this->assertSame(['footer'], $keys['footer']);
    }

    public function test_default_layout_structure(): void
    {
        $layout = $this->parser->defaultLayout('simple');

        $this->assertSame('simple', $layout['type']);
        $this->assertSame(['announcement', 'header'], $layout['header']['order']);
        $this->assertSame(['footer'], $layout['footer']['order']);

        $this->assertArrayHasKey('header', $layout['header']['sections']);
        $this->assertSame('header', $layout['header']['sections']['header']['type']);
    }

    public function test_extract_zoned_keys_returns_empty_for_missing_layout(): void
    {
        $keys = $this->parser->extractZonedKeys('nonexistent');

        $this->assertSame([], $keys['header']);
        $this->assertSame([], $keys['footer']);
    }
}
