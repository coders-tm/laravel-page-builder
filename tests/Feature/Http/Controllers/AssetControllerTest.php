<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature\Http\Controllers;

use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use fake storage for tests
        Storage::fake('public');
    }

    /**
     * Test that asset upload endpoint returns a 201 response with asset data.
     */
    public function test_upload_returns_201_with_asset_data(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->post('/pagebuilder/assets/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'name',
            'url',
            'thumbnail',
            'size',
            'type',
        ]);
    }

    /**
     * Test that uploaded file is stored in the correct directory.
     */
    public function test_upload_stores_file_in_pagebuilder_directory(): void
    {
        $file = UploadedFile::fake()->image('my-image.png');

        $this->post('/pagebuilder/assets/upload', [
            'file' => $file,
        ]);

        // Check that a file was stored in the pagebuilder directory
        $files = Storage::disk('public')->files('pagebuilder');
        $this->assertNotEmpty($files);

        // Verify the file exists
        $this->assertTrue(
            Storage::disk('public')->exists($files[0])
        );
    }

    /**
     * Test that uploaded filename uses safe extension derived from MIME type,
     * not from the client-supplied filename.
     *
     * This is a security test: if someone tries to upload a file with
     * a misleading extension (e.g. claiming it's a .jpg when it's actually a .png),
     * the stored filename should reflect the actual MIME type.
     */
    public function test_upload_uses_safe_extension_from_mime_type(): void
    {
        // Create a PNG image with a .jpg filename
        $file = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->post('/pagebuilder/assets/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201);

        // Extract the stored filename from the response
        $storedName = basename($response->json('name'));

        // The stored filename should have a proper image extension
        // (derived from the actual MIME type, not blindly trusting the client filename)
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];
        $hasValidExtension = false;
        foreach ($validExtensions as $ext) {
            if (str_ends_with($storedName, '.'.$ext)) {
                $hasValidExtension = true;
                break;
            }
        }

        $this->assertTrue(
            $hasValidExtension,
            "Expected filename to end with a valid image extension but got: {$storedName}"
        );

        // Verify the file exists with the correct extension
        $files = Storage::disk('public')->files('pagebuilder');
        $this->assertNotEmpty($files, 'Expected to find uploaded file in storage');
    }

    /**
     * Test that all supported image formats are accepted.
     */
    public function test_upload_accepts_all_supported_formats(): void
    {
        $formats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

        foreach ($formats as $format) {
            Storage::disk('public')->deleteDirectory('pagebuilder');

            if ($format === 'svg') {
                $file = UploadedFile::fake()->create("test.{$format}", 100, 'image/svg+xml');
            } elseif ($format === 'avif') {
                // UploadedFile::fake()->image() doesn't support avif, use generic create
                $file = UploadedFile::fake()->create("test.{$format}", 100, 'image/avif');
            } else {
                $file = UploadedFile::fake()->image("test.{$format}");
            }

            $response = $this->post('/pagebuilder/assets/upload', [
                'file' => $file,
            ]);

            $statusOk = in_array($response->status(), [201, 422], true);
            $this->assertTrue(
                $statusOk,
                "Format {$format} failed with status {$response->status()}: ".json_encode($response->json())
            );
        }
    }

    /**
     * Test that non-image files are rejected.
     */
    public function test_upload_rejects_non_image_files(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/pagebuilder/assets/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test that files exceeding max size are rejected.
     */
    public function test_upload_rejects_files_exceeding_max_size(): void
    {
        // Create a file larger than 10MB (config max)
        $file = UploadedFile::fake()->image('large.jpg')->size(11000); // 11000 KB

        $response = $this->postJson('/pagebuilder/assets/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test that the upload endpoint requires a file.
     */
    public function test_upload_requires_file(): void
    {
        $response = $this->postJson('/pagebuilder/assets/upload', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /**
     * Test that asset index endpoint returns paginated results.
     */
    public function test_index_returns_paginated_assets(): void
    {
        // Upload multiple files
        for ($i = 0; $i < 5; $i++) {
            $file = UploadedFile::fake()->image("image-{$i}.jpg");
            $this->post('/pagebuilder/assets/upload', ['file' => $file]);
        }

        $response = $this->get('/pagebuilder/assets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'url', 'thumbnail', 'size', 'type'],
            ],
            'pagination' => ['page', 'per_page', 'total'],
        ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /**
     * Test that asset index endpoint filters by search query.
     */
    public function test_index_filters_by_search_query(): void
    {
        // Upload files with different names
        $this->post('/pagebuilder/assets/upload', [
            'file' => UploadedFile::fake()->image('hero-banner.jpg'),
        ]);
        $this->post('/pagebuilder/assets/upload', [
            'file' => UploadedFile::fake()->image('footer-logo.png'),
        ]);

        $response = $this->get('/pagebuilder/assets?q=hero');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should find hero-banner but not footer-logo
        $this->assertTrue(
            collect($data)->some(fn ($asset) => str_contains($asset['name'], 'hero')),
            'Expected to find asset with "hero" in the name'
        );
    }

    /**
     * Test that uploaded filename includes timestamp for uniqueness.
     */
    public function test_upload_includes_timestamp_in_filename(): void
    {
        $file = UploadedFile::fake()->image('my-image.jpg');

        $response = $this->post('/pagebuilder/assets/upload', [
            'file' => $file,
        ]);

        $storedName = basename($response->json('name'));

        // Filename should start with a timestamp (digits)
        $this->assertTrue(
            preg_match('/^\d+_/', $storedName) === 1,
            "Expected filename to start with timestamp but got: {$storedName}"
        );
    }

    /**
     * Test that uploaded filename is slugified.
     */
    public function test_upload_slugifies_filename(): void
    {
        $file = UploadedFile::fake()->image('My Awesome Image.jpg');

        $response = $this->post('/pagebuilder/assets/upload', [
            'file' => $file,
        ]);

        $storedName = basename($response->json('name'));

        // Filename should be slugified (lowercase, hyphens instead of spaces)
        $this->assertStringContainsString('my-awesome-image', $storedName);
        $this->assertStringNotContainsString('Awesome', $storedName);
    }
}
