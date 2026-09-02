<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Services;

use PageBuilder\Services\TemplateStorage;
use PageBuilder\Tests\TestCase;
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

    public function test_loads_templates_from_configured_templates_path(): void
    {
        $customPath = sys_get_temp_dir().'/pb_test_templates_'.uniqid();
        mkdir($customPath);

        try {
            file_put_contents($customPath.'/custom.json', json_encode([
                'sections' => ['main' => ['type' => 'page-content']],
                'order' => ['main'],
            ]));

            config(['pagebuilder.templates' => $customPath]);

            $storage = new TemplateStorage;
            $data = $storage->load('custom');

            $this->assertIsArray($data);
            $this->assertSame(['main'], $data['order']);
            $this->assertSame('page-content', $data['sections']['main']['type']);
        } finally {
            File::deleteDirectory($customPath);
        }
    }

    public function test_all_returns_templates_from_configured_path(): void
    {
        $customPath = sys_get_temp_dir().'/pb_test_templates_all_'.uniqid();
        mkdir($customPath);

        try {
            file_put_contents($customPath.'/my-layout.json', json_encode([
                'sections' => [],
                'order' => [],
            ]));

            config(['pagebuilder.templates' => $customPath]);

            $storage = new TemplateStorage;
            $templates = $storage->all();

            $values = array_column($templates, 'value');
            $this->assertContains('my-layout', $values);
        } finally {
            File::deleteDirectory($customPath);
        }
    }

    public function test_returns_null_for_template_absent_from_configured_path_and_theme(): void
    {
        // 'nonexistent-xyz' does not exist in the workbench templates or the theme,
        // so load() must return null regardless of the configured path.
        $this->assertNull($this->storage->load('nonexistent-xyz'));
    }

    public function test_configured_templates_path_is_used_before_fallback(): void
    {
        // Verify that a template only present in the custom path IS loaded,
        // confirming the configured path is consulted.
        $customPath = sys_get_temp_dir().'/pb_test_templates_primary_'.uniqid();
        mkdir($customPath);

        try {
            file_put_contents($customPath.'/only-in-custom.json', json_encode([
                'sections' => ['s' => ['type' => 'hero']],
                'order' => ['s'],
            ]));

            config(['pagebuilder.templates' => $customPath]);

            $storage = new TemplateStorage;
            $data = $storage->load('only-in-custom');

            $this->assertIsArray($data);
            $this->assertSame(['s'], $data['order']);
        } finally {
            File::deleteDirectory($customPath);
        }
    }
}
