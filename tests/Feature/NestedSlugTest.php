<?php

declare(strict_types=1);

use PageBuilder\Facades\Page;
use Workbench\App\Models\Page as ModelsPage;

test('can access nested slug api', function () {
    // 1. Create a page with a nested slug in DB
    ModelsPage::create([
        'slug' => 'parent/child',
        'title' => 'Nested Page',
        'is_active' => true,
    ]);

    // 2. Try to access the API route — new pattern: GET /pagebuilder/{slug}.json
    $response = $this->get('/pagebuilder/parent/child.json');

    $response->assertOk();
});
test('can visit nested slug public page', function () {
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
});
