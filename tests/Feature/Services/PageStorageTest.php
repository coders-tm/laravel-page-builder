<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Services;

use Coderstm\PageBuilder\Services\PageStorage;
use Coderstm\PageBuilder\Support\PageData;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class PageStorageTest extends TestCase
{
    private PageStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = $this->app->make(PageStorage::class);
    }

    public function test_save_and_load(): void
    {
        $data = [
            'sections' => [
                'hero' => [
                    'type' => 'hero',
                    'settings' => ['title' => 'Hello'],
                    'blocks' => [],
                    'order' => [],
                ],
            ],
            'order' => ['hero'],
            'title' => 'Test Page',
        ];

        $this->assertTrue($this->storage->save('test-page', $data));

        $loaded = $this->storage->load('test-page');
        $this->assertInstanceOf(PageData::class, $loaded);
        // title is a DB-only field — stripped on save, not present in JSON
        $this->assertSame('', $loaded->title());
        $this->assertSame(['hero'], $loaded->order());
    }

    public function test_load_returns_null_for_missing_page(): void
    {
        $this->assertNull($this->storage->load('nonexistent'));
    }

    public function test_save_with_page_data_object(): void
    {
        $pageData = PageData::fromArray([
            'sections' => [],
            'order' => [],
            'title' => 'From PageData',
        ]);

        $this->assertTrue($this->storage->save('from-object', $pageData));

        $loaded = $this->storage->load('from-object');
        // title is a DB-only field — not persisted to JSON
        $this->assertSame('', $loaded->title());
    }

    public function test_save_overwrites_existing(): void
    {
        $this->storage->save('overwrite', [
            'sections' => [],
            'order' => [],
            'title' => 'First',
        ]);

        $this->storage->save('overwrite', [
            'sections' => [],
            'order' => [],
            'title' => 'Second',
        ]);

        $loaded = $this->storage->load('overwrite');
        // title is a DB-only field — not persisted to JSON; verify sections are overwritten
        $this->assertSame('', $loaded->title());
        $this->assertSame([], $loaded->order());
    }

    public function test_load_returns_null_for_invalid_json(): void
    {
        $path = config('pagebuilder.pages').'/invalid.json';
        file_put_contents($path, 'not valid json');

        $this->assertNull($this->storage->load('invalid'));

        // Cleanup
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function test_preserved_page_persists_title_and_meta(): void
    {
        $data = [
            'sections' => [
                'banner-1' => [
                    'type' => 'banner',
                    'settings' => ['text' => 'Welcome Home'],
                ],
            ],
            'order' => ['banner-1'],
            'title' => 'Home Page',
            'meta' => [
                'meta_title' => 'SEO Home',
                'meta_description' => 'Home description',
            ],
        ];

        // 'home' is a preserved page by default
        $this->assertTrue($this->storage->save('home', $data));

        $loaded = $this->storage->load('home');
        $this->assertSame('Home Page', $loaded->title());
        $this->assertSame('SEO Home', $loaded->meta()['meta_title']);
        $this->assertSame('Home description', $loaded->meta()['meta_description']);

        // Verify JSON file directly to be absolutely sure
        $filePath = config('pagebuilder.pages').'/home.json';
        $json = json_decode(file_get_contents($filePath), true);
        $this->assertArrayHasKey('title', $json);
        $this->assertArrayHasKey('meta', $json);
    }

    public function test_regular_page_strips_title_and_meta(): void
    {
        $data = [
            'sections' => [],
            'order' => [],
            'title' => 'Regular Page',
            'meta' => [
                'meta_title' => 'SEO Regular',
            ],
        ];

        $this->assertTrue($this->storage->save('regular-page', $data));

        $loaded = $this->storage->load('regular-page');
        $this->assertSame('', $loaded->title());
        $this->assertSame([], $loaded->meta());

        // Verify JSON file directly
        $filePath = config('pagebuilder.pages').'/regular-page.json';
        $json = json_decode(file_get_contents($filePath), true);
        $this->assertArrayNotHasKey('title', $json);
        $this->assertArrayNotHasKey('meta', $json);
    }
}
