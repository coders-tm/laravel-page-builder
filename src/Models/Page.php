<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PageBuilder\PageBuilder;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'parent',
        'title',
        'slug',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'is_active',
        'template', // layout template
        'metadata',
        'content',
    ];

    protected $casts = ['is_active' => 'boolean', 'metadata' => 'json'];

    protected $appends = ['url'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')->preventOverwrite();
    }

    public static function findActiveBySlug(string $slug): static
    {
        return static::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public function getUrlAttribute()
    {
        $path = $this->slug;
        $parent = $this->parent;
        if ($parent) {
            $path = $parent.'/'.$path;
        }

        return url($path);
    }

    protected static function booted()
    {
        static::saving(function ($page) {
            if (PageBuilder::isPreservedPage($page->slug)) {
                throw new \InvalidArgumentException("The slug '{$page->slug}' is reserved and cannot be used for dynamic pages.");
            }
        });
    }
}
