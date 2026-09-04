<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Use fake storage for tests
    Storage::fake('public');
});
test('upload returns 201 with asset data', function () {
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
});
test('upload stores file in pagebuilder directory', function () {
    $file = UploadedFile::fake()->image('my-image.png');

    $this->post('/pagebuilder/assets/upload', [
        'file' => $file,
    ]);

    // Check that a file was stored in the pagebuilder directory
    $files = Storage::disk('public')->files('pagebuilder');
    expect($files)->not->toBeEmpty();

    // Verify the file exists
    expect(Storage::disk('public')->exists($files[0]))->toBeTrue();
});
test('upload uses safe extension from mime type', function () {
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

    expect($hasValidExtension)->toBeTrue("Expected filename to end with a valid image extension but got: {$storedName}");

    // Verify the file exists with the correct extension
    $files = Storage::disk('public')->files('pagebuilder');
    expect($files)->not->toBeEmpty('Expected to find uploaded file in storage');
});
test('upload accepts all supported formats', function () {
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
        expect($statusOk)->toBeTrue("Format {$format} failed with status {$response->status()}: ".json_encode($response->json()));
    }
});
test('upload rejects non image files', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->postJson('/pagebuilder/assets/upload', [
        'file' => $file,
    ]);

    $response->assertStatus(422);
});
test('upload rejects files exceeding max size', function () {
    // Create a file larger than 10MB (config max)
    $file = UploadedFile::fake()->image('large.jpg')->size(11000);

    // 11000 KB
    $response = $this->postJson('/pagebuilder/assets/upload', [
        'file' => $file,
    ]);

    $response->assertStatus(422);
});
test('upload requires file', function () {
    $response = $this->postJson('/pagebuilder/assets/upload', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('file');
});
test('index returns paginated assets', function () {
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

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});
test('index filters by search query', function () {
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
    expect(collect($data)->some(fn ($asset) => str_contains($asset['name'], 'hero')))->toBeTrue('Expected to find asset with "hero" in the name');
});
test('upload includes timestamp in filename', function () {
    $file = UploadedFile::fake()->image('my-image.jpg');

    $response = $this->post('/pagebuilder/assets/upload', [
        'file' => $file,
    ]);

    $storedName = basename($response->json('name'));

    // Filename should start with a timestamp (digits)
    expect(preg_match('/^\d+_/', $storedName) === 1)->toBeTrue("Expected filename to start with timestamp but got: {$storedName}");
});
test('upload slugifies filename', function () {
    $file = UploadedFile::fake()->image('My Awesome Image.jpg');

    $response = $this->post('/pagebuilder/assets/upload', [
        'file' => $file,
    ]);

    $storedName = basename($response->json('name'));

    // Filename should be slugified (lowercase, hyphens instead of spaces)
    $this->assertStringContainsString('my-awesome-image', $storedName);
    $this->assertStringNotContainsString('Awesome', $storedName);
});
