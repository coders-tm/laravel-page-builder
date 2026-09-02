<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature;

use PageBuilder\Facades\Page;
use PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Models\Page as ModelsPage;

class NestedSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_nested_slug_api(): void
    {
        // 1. Create a page with a nested slug in DB
        ModelsPage::create([
            'slug' => 'parent/child',
            'title' => 'Nested Page',
            'is_active' => true,
        ]);

        // 2. Try to access the API route — new pattern: GET /pagebuilder/{slug}.json
        $response = $this->get('/pagebuilder/parent/child.json');

        $response->assertOk();
    }

    public function test_can_visit_nested_slug_public_page(): void
    {
        // 1. Create a page with a nested slug in DB
        ModelsPage::create([
            'slug' => 'child',
            'parent' => 'parent',
            'title' => 'Nested Page',
            'is_active' => true,
        ]);

        // 2. Register routes
        Page::routes();

        // 3. Try to access the public route
        // This works because Page::routes() creates 'parent/child' route explicitly
        // and defaults 'slug' to 'child'.
        $response = $this->get('/parent/child');

        $response->assertOk();
    }
}
