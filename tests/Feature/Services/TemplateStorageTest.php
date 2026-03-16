<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Services\TemplateStorage;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class TemplateStorageTest extends TestCase
{
    private TemplateStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = $this->app->make(TemplateStorage::class);
    }

    public function test_loads_default_page_template(): void
    {
        $data = $this->storage->load('page');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('sections', $data);
        $this->assertArrayHasKey('order', $data);
        $this->assertSame(['main'], $data['order']);
    }

    public function test_loads_alternate_template(): void
    {
        $data = $this->storage->load('page.alternate');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('wrapper', $data);
        $this->assertSame('main#page-alternate.page-wrapper', $data['wrapper']);
    }

    public function test_returns_null_for_missing_template(): void
    {
        $this->assertNull($this->storage->load('nonexistent'));
    }

    public function test_normalizes_name_with_json_extension(): void
    {
        $data = $this->storage->load('page.json');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('sections', $data);
    }

    public function test_normalizes_empty_name_to_page(): void
    {
        // Empty string should fall back to 'page'
        $data = $this->storage->load('');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('sections', $data);
    }

    public function test_returns_null_for_invalid_json(): void
    {
        $path = config('pagebuilder.templates').'/broken.json';
        file_put_contents($path, 'not valid json');

        $this->assertNull($this->storage->load('broken'));

        File::delete($path);
    }

    public function test_template_sections_contain_expected_type(): void
    {
        $data = $this->storage->load('page');

        $this->assertSame('page-content', $data['sections']['main']['type']);
    }

    public function test_loads_variable_template(): void
    {
        $data = $this->storage->load('page.var');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('sections', $data);

        $settings = $data['sections']['title-banner']['settings'] ?? [];
        $this->assertSame('{{ $page->title }}', $settings['text']);
    }
}
