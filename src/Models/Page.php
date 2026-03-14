<?php

namespace Coderstm\PageBuilder\Models;

use Coderstm\PageBuilder\PageBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model
{
    use HasFactory, HasSlug;

    protected static function booted()
    {
        static::saving(function ($page) {
            if (PageBuilder::isPreservedPage($page->slug)) {
                throw new \InvalidArgumentException("The slug '{$page->slug}' is reserved and cannot be used for dynamic pages.");
            }
        });
    }

    protected $logIgnore = [
        'metadata',
    ];

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
}
