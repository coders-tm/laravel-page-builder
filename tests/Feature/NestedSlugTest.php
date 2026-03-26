<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Feature;

use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Models\Page as ModelsPage;

class NestedSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_nested_slug_editor(): void
    {
        // 1. Create a page with a nested slug in DB
        ModelsPage::create([
            'slug' => 'parent/child',
            'title' => 'Nested Page',
            'is_active' => true,
        ]);

        // 2. Register routes
        Page::routes();

        // 3. Try to access the editor route
        // This is expected to FAIL (404) before the fix
        $response = $this->get('/pagebuilder/parent/child');

        $response->assertOk();
    }

    public function test_can_access_nested_slug_api(): void
    {
        // 1. Create a page with a nested slug in DB
        ModelsPage::create([
            'slug' => 'parent/child',
            'title' => 'Nested Page',
            'is_active' => true,
        ]);

        // 2. Try to access the API route
        // This is expected to FAIL (404) before the fix
        $response = $this->get('/pagebuilder/page/parent/child');

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
