<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature;

use PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ThemeAssetVersioningTest extends TestCase
{
    private string $publicBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicBase = public_path('themes/test-theme');
        File::makeDirectory("{$this->publicBase}/css", 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::exists(public_path('themes'))) {
            File::deleteDirectory(public_path('themes'));
        }

        parent::tearDown();
    }

    public function test_appends_version_query_param_when_file_exists(): void
    {
        $file = "{$this->publicBase}/css/app.css";
        File::put($file, 'body { color: #000; }');

        // Ensure a stable mtime we can assert against
        $mtime = time() - 1234;
        @touch($file, $mtime);
        $url = theme('css/app.css', 'test-theme');

        $this->assertIsString($url);

        $matched = preg_match('/[?&]v=(\d+)/', $url, $matches);
        $this->assertSame(1, $matched, 'Expected version query param in URL: '.$url);
        $this->assertEquals((string) $mtime, $matches[1]);
    }
}
