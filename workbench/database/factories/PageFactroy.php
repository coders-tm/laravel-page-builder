<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Page;

/**
 * @template TModel of \Workbench\App\Models\Page
 *
 * @extends Factory<TModel>
 */
class PageFactroy extends Factory
{
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'slug' => fake()->slug(),
            'content' => fake()->paragraphs(3, true),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the page is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * With meta data.
     */
    public function meta(array $meta = []): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_title' => $meta['title'] ?? fake()->sentence(),
            'meta_keywords' => $meta['keywords'] ?? fake()->words(5, true),
            'meta_description' => $meta['description'] ?? fake()->sentence(),
        ]);
    }
}
