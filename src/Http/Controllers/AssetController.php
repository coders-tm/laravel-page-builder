<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /**
     * The storage disk used for page builder assets.
     */
    protected string $disk;

    /**
     * The storage directory for page builder assets.
     */
    protected string $directory;

    public function __construct()
    {
        $this->disk = config('pagebuilder.disk', 'public');
        $this->directory = config('pagebuilder.asset_directory', 'pagebuilder');
    }

    /**
     * GET /pagebuilder/assets
     *
     * List assets with optional search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));
        $search = $request->query('q', '');

        $allFiles = Storage::disk($this->disk)->files($this->directory);

        // Filter to image files only
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];
        $files = collect($allFiles)->filter(function ($file) use ($imageExtensions) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            return in_array($ext, $imageExtensions);
        });

        // Apply search filter
        if ($search) {
            $files = $files->filter(function ($file) use ($search) {
                return str_contains(
                    strtolower(basename($file)),
                    strtolower($search)
                );
            });
        }

        // Sort by newest first (by filename since we prepend timestamp)
        $files = $files->sortDesc()->values();

        $total = $files->count();
        $offset = ($page - 1) * $perPage;
        $pageFiles = $files->slice($offset, $perPage)->values();

        $assets = $pageFiles->map(function ($file) {
            return $this->fileToAsset($file);
        });

        return response()->json([
            'data' => $assets,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * POST /pagebuilder/assets/upload
     *
     * Upload an asset file.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg,avif|max:10240',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // Prefix with timestamp for unique naming and sorting
        // Use Laravel's extension() method which derives the extension from the actual MIME type,
        // not from the client-supplied filename. This prevents stored files from retaining
        // malicious client-controlled extensions.
        $ext = $file->extension();
        $filename = time().'_'.Str::slug(
            pathinfo($originalName, PATHINFO_FILENAME)
        ).'.'.$ext;

        $path = $file->storeAs($this->directory, $filename, $this->disk);

        return response()->json(
            $this->fileToAsset($path),
            201
        );
    }

    /**
     * Convert a storage file path to an Asset object.
     */
    protected function fileToAsset(string $file): array
    {
        $url = Storage::disk($this->disk)->url($file);
        $size = Storage::disk($this->disk)->size($file);
        $name = basename($file);

        return [
            'id' => base64_encode($file),
            'name' => $name,
            'url' => $url,
            'thumbnail' => $url,
            'size' => $size,
            'type' => 'image',
        ];
    }
}
