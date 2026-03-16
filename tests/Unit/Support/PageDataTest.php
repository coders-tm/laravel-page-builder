<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Unit\Support;

use Coderstm\PageBuilder\Support\PageData;
use PHPUnit\Framework\TestCase;

class PageDataTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'sections' => [
                'hero' => [
                    'type' => 'hero',
                    'settings' => ['title' => 'Welcome'],
                    'blocks' => [],
                    'order' => [],
                    'disabled' => false,
                ],
                'footer' => [
                    'type' => 'footer',
                    'settings' => [],
                    'blocks' => [],
                    'order' => [],
                    'disabled' => false,
                ],
            ],
            'order' => ['hero', 'footer'],
            'title' => 'Home Page',
        ];
    }

    private function sampleDataWithLayout(): array
    {
        $data = $this->sampleData();
        $data['layout'] = [
            'type' => 'page',
            'header' => [
                'sections' => [
                    'header' => [
                        'type' => 'header',
                        'disabled' => false,
                        'settings' => ['logo' => '/img/logo.png', 'menu' => 'menu-1', 'sticky' => true],
                        'blocks' => [],
                        'order' => [],
                    ],
                    'disabled-section' => [
                        'type' => 'promo',
                        'disabled' => true,
                        'settings' => ['text' => 'Promo text'],
                    ],
                ],
                'order' => ['header', 'disabled-section'],
            ],
            'footer' => [
                'sections' => [
                    'footer' => [
                        'type' => 'footer',
                        'disabled' => false,
                        'settings' => ['tagline' => 'Best gym ever'],
                    ],
                ],
                'order' => ['footer'],
            ],
        ];

        return $data;
    }

    public function test_from_array(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertInstanceOf(PageData::class, $data);
        $this->assertSame('Home Page', $data->title());
    }

    public function test_from_array_with_empty_array(): void
    {
        $data = PageData::fromArray([]);

        $this->assertTrue($data->isEmpty());
        $this->assertSame([], $data->order());
        $this->assertSame([], $data->sections());
    }

    public function test_from_array_with_missing_keys(): void
    {
        $data = PageData::fromArray(['foo' => 'bar']);

        $this->assertTrue($data->isEmpty());
        $this->assertSame('', $data->title());
        $this->assertSame([], $data->sections());
    }

    public function test_order(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertSame(['hero', 'footer'], $data->order());
    }

    public function test_sections(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $sections = $data->sections();
        $this->assertCount(2, $sections);
        $this->assertArrayHasKey('hero', $sections);
        $this->assertArrayHasKey('footer', $sections);
    }

    public function test_section_by_id(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $hero = $data->section('hero');
        $this->assertSame('hero', $hero['type']);
        $this->assertSame('Welcome', $hero['settings']['title']);
    }

    public function test_section_returns_null_for_missing_id(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertNull($data->section('nonexistent'));
    }

    public function test_title(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertSame('Home Page', $data->title());
    }

    public function test_title_defaults_to_empty_string(): void
    {
        $data = PageData::fromArray(['sections' => [], 'order' => []]);

        $this->assertSame('', $data->title());
    }

    public function test_is_empty(): void
    {
        $empty = PageData::fromArray([]);
        $this->assertTrue($empty->isEmpty());

        $notEmpty = PageData::fromArray($this->sampleData());
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function test_is_not_empty(): void
    {
        $data = PageData::fromArray($this->sampleData());
        $this->assertTrue($data->isNotEmpty());

        $empty = PageData::fromArray([]);
        $this->assertFalse($empty->isNotEmpty());
    }

    public function test_to_array(): void
    {
        $input = $this->sampleData();
        $data = PageData::fromArray($input);
        $output = $data->toArray();

        $this->assertArrayHasKey('sections', $output);
        $this->assertArrayHasKey('order', $output);
        $this->assertSame(['hero', 'footer'], $output['order']);
    }

    public function test_to_json(): void
    {
        $data = PageData::fromArray($this->sampleData());
        $json = $data->toJson();

        $decoded = json_decode($json, true);
        // title and meta are now part of the PageData DTO for the editor
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('meta', $decoded);
        $this->assertSame('Home Page', $decoded['title']);
        $this->assertSame(['hero', 'footer'], $decoded['order']);
    }

    public function test_json_serializable(): void
    {
        $data = PageData::fromArray($this->sampleData());
        $json = json_encode($data);

        $decoded = json_decode($json, true);
        // title and meta are now part of the PageData DTO for the editor
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('meta', $decoded);
        $this->assertSame('Home Page', $decoded['title']);
    }

    // ─── Layout Tests ──────────────────────────────────────────

    public function test_layout_type_defaults_to_page(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertSame('page', $data->layoutType());
    }

    public function test_layout_type_returns_configured_type(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());

        $this->assertSame('page', $data->layoutType());
    }

    public function test_layout_type_with_custom_type(): void
    {
        $input = $this->sampleData();
        $input['layout'] = ['type' => 'landing'];
        $data = PageData::fromArray($input);

        $this->assertSame('landing', $data->layoutType());
    }

    public function test_layout_section_returns_raw_array(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());

        $header = $data->layoutSection('header');

        $this->assertIsArray($header);
        $this->assertSame('header', $header['type']);
        $this->assertSame('/img/logo.png', $header['settings']['logo']);
        $this->assertSame('menu-1', $header['settings']['menu']);
        $this->assertTrue($header['settings']['sticky']);
    }

    public function test_layout_section_returns_null_for_absent_key(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());

        $this->assertNull($data->layoutSection('sidebar'));
    }

    public function test_layout_section_returns_null_when_disabled(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());

        // disabled-section is in the header zone with disabled: true
        $this->assertNull($data->layoutSection('disabled-section'));
    }

    public function test_layout_section_normalises_missing_blocks_and_order(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());

        // The 'footer' section has no 'blocks' or 'order' keys in the fixture
        $footer = $data->layoutSection('footer');

        $this->assertIsArray($footer);
        $this->assertSame([], $footer['blocks']);
        $this->assertSame([], $footer['order']);
    }

    public function test_layout_section_returns_null_when_no_layout(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertNull($data->layoutSection('header'));
    }

    public function test_layout_sections_returns_all_normalised(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());

        // layoutSections() flattens both zones — header(2) + footer(1) = 3
        $sections = $data->layoutSections();

        $this->assertCount(3, $sections);
        $this->assertArrayHasKey('header', $sections);
        $this->assertArrayHasKey('footer', $sections);
        $this->assertArrayHasKey('disabled-section', $sections);

        foreach ($sections as $section) {
            $this->assertArrayHasKey('blocks', $section);
            $this->assertArrayHasKey('order', $section);
        }
    }

    public function test_layout_sections_returns_empty_when_no_layout(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertSame([], $data->layoutSections());
    }

    public function test_to_array_includes_layout(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());
        $output = $data->toArray();

        $this->assertArrayHasKey('layout', $output);
        $this->assertSame('page', $output['layout']['type']);

        // header zone has 2 sections (header + disabled-section)
        $this->assertArrayHasKey('header', $output['layout']);
        $this->assertArrayHasKey('footer', $output['layout']);
        $this->assertCount(2, $output['layout']['header']['sections']);
        $this->assertCount(1, $output['layout']['footer']['sections']);
    }

    public function test_to_array_includes_default_layout_when_absent(): void
    {
        // No layout stored — toArray() returns empty layout
        $data = PageData::fromArray($this->sampleData());
        $output = $data->toArray();

        $this->assertArrayHasKey('layout', $output);
        $this->assertSame([], $output['layout']);
    }

    public function test_to_json_includes_layout(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());
        $json = $data->toJson();
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('layout', $decoded);
        $this->assertSame('page', $decoded['layout']['type']);
        $this->assertArrayHasKey('header', $decoded['layout']);
        $this->assertArrayHasKey('footer', $decoded['layout']);
        // Total sections across both zones: 2 header + 1 footer
        $this->assertCount(2, $decoded['layout']['header']['sections']);
        $this->assertCount(1, $decoded['layout']['footer']['sections']);
    }

    public function test_layout_header_zone(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());
        $header = $data->layoutHeader();

        $this->assertArrayHasKey('sections', $header);
        $this->assertArrayHasKey('order', $header);
        $this->assertCount(2, $header['sections']);
        $this->assertSame(['header', 'disabled-section'], $header['order']);
    }

    public function test_layout_footer_zone(): void
    {
        $data = PageData::fromArray($this->sampleDataWithLayout());
        $footer = $data->layoutFooter();

        $this->assertArrayHasKey('sections', $footer);
        $this->assertArrayHasKey('order', $footer);
        $this->assertCount(1, $footer['sections']);
        $this->assertSame(['footer'], $footer['order']);
    }

    public function test_layout_section_with_blocks(): void
    {
        $input = $this->sampleData();
        $input['layout'] = [
            'type' => 'page',
            'header' => [
                'sections' => [
                    'header' => [
                        'type' => 'header',
                        'disabled' => false,
                        'settings' => ['logo' => '/logo.png'],
                        'blocks' => [
                            'nav-row' => [
                                'type' => 'row',
                                'settings' => ['columns' => '3'],
                                'blocks' => [],
                                'order' => [],
                            ],
                        ],
                        'order' => ['nav-row'],
                    ],
                ],
                'order' => ['header'],
            ],
            'footer' => ['sections' => [], 'order' => []],
        ];

        $data = PageData::fromArray($input);
        $header = $data->layoutSection('header');

        $this->assertCount(1, $header['blocks']);
        $this->assertArrayHasKey('nav-row', $header['blocks']);
        $this->assertSame(['nav-row'], $header['order']);
    }

    // ── wrapper ───────────────────────────────────────────────────────────────

    public function test_wrapper_defaults_to_null(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $this->assertNull($data->wrapper());
    }

    public function test_from_array_reads_wrapper(): void
    {
        $data = PageData::fromArray([
            ...$this->sampleData(),
            'wrapper' => 'main#content.container',
        ]);

        $this->assertSame('main#content.container', $data->wrapper());
    }

    public function test_from_array_ignores_empty_wrapper_string(): void
    {
        $data = PageData::fromArray([
            ...$this->sampleData(),
            'wrapper' => '',
        ]);

        $this->assertNull($data->wrapper());
    }

    public function test_from_array_ignores_non_string_wrapper(): void
    {
        $data = PageData::fromArray([
            ...$this->sampleData(),
            'wrapper' => 123,
        ]);

        $this->assertNull($data->wrapper());
    }

    public function test_to_array_includes_wrapper_when_set(): void
    {
        $data = PageData::fromArray([
            ...$this->sampleData(),
            'wrapper' => 'div#main',
        ]);

        $array = $data->toArray();
        $this->assertArrayHasKey('wrapper', $array);
        $this->assertSame('div#main', $array['wrapper']);
    }

    public function test_to_array_omits_wrapper_when_null(): void
    {
        $data = PageData::fromArray($this->sampleData());

        $array = $data->toArray();
        $this->assertArrayNotHasKey('wrapper', $array);
    }
}
