<?php

namespace Coderstm\PageBuilder\Services;

use Coderstm\PageBuilder\Http\Controllers\WebPageController;
use Coderstm\PageBuilder\PageBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class PageService
{
    public function routes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        foreach (app(PageRegistry::class)->pages() as $page) {
            $slug = $page['slug'];
            $parent = $page['parent'] ?? null;
            $path = $parent ? "{$parent}/{$slug}" : $slug;

            if ($path) {
                Route::get($path, [WebPageController::class, 'pages'])
                    ->defaults('slug', $slug)
                    ->name('pages.'.$slug);
            }
        }

        Route::get('/', [WebPageController::class, 'pages'])
            ->defaults('slug', 'home')
            ->name('pages.home');
    }

    /**
     * Return all active pages keyed by slug, in the shape expected by PageRegistry.
     */
    public function allActive(): array
    {
        return PageBuilder::$pageModel::where('is_active', true)
            ->get()
            ->keyBy('slug')
            ->map(fn ($page) => [
                'id' => $page->id,
                'slug' => $page->slug,
                'parent' => $page->parent,
                'title' => $page->title,
            ])
            ->all();
    }

    /**
     * Find a DB page record by slug.
     */
    public function findBySlug(string $slug): ?Model
    {
        return PageBuilder::$pageModel::where('slug', $slug)->first();
    }

    /**
     * Persist page meta fields to the database.
     *
     * Only non-null values are written so that empty strings do not
     * accidentally clear fields the editor never touched.
     */
    public function saveMeta(string $slug, array $meta): bool
    {
        if (empty($meta)) {
            return true;
        }

        $fillable = array_filter([
            'title' => $meta['title'] ?? null,
            'meta_title' => $meta['meta_title'] ?? null,
            'meta_description' => $meta['meta_description'] ?? null,
            'meta_keywords' => $meta['meta_keywords'] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($fillable)) {
            return true;
        }

        return (bool) PageBuilder::$pageModel::where('slug', $slug)->update($fillable);
    }
}
