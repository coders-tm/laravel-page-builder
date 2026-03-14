# Laravel Page Builder

<p align="center">
<a href="https://github.com/coders-tm/laravel-page-builder/actions"><img src="https://github.com/coders-tm/laravel-page-builder/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/coderstm/laravel-page-builder"><img src="https://img.shields.io/packagist/dt/coderstm/laravel-page-builder" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/coderstm/laravel-page-builder"><img src="https://img.shields.io/packagist/v/coderstm/laravel-page-builder" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/coderstm/laravel-page-builder"><img src="https://img.shields.io/packagist/l/coderstm/laravel-page-builder" alt="License"></a>
</p>

A modern page builder for Laravel that allows you to build dynamic pages using layouts, sections and JSON rendering.
It includes a visual editor, layout system, reusable sections and multi-theme support.

## Features

- **Blade-native rendering** — sections and blocks are regular Blade views with typed PHP objects
- **`@schema()` directive** — declare settings, child blocks, and presets directly in Blade templates
- **Visual editor** — React SPA with iframe live preview, drag-and-drop, and inline text editing
- **JSON-based storage** — page data stored as JSON files on disk for fast reads and easy version control
- **Per-page Layouts** — site header and footer are configurable per-page, stored in the page JSON
- **Recursive block nesting** — container blocks (rows, columns) can hold child blocks to any depth
- **Theme blocks** — register global block types that any section can accept via `@theme` wildcard
- **21+ Field Types** — from basic text inputs to advanced color pickers, icon selectors, and custom types
- **Page Meta Persistence** — SEO titles and descriptions are automatically managed and persisted across dynamic and preserved pages
- **Editor mode** — `data-editor-*` attributes injected only when the editor is active
- **Publishable assets** — config, views, migrations, and frontend assets can be published independently

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

```bash
composer require coderstm/laravel-page-builder
```

The package auto-registers its service provider via Laravel's package discovery.

### Publish Configuration

```bash
php artisan vendor:publish --tag=pagebuilder-config
```

This publishes `config/pagebuilder.php` with the following options:

```php
return [
    // Path to page JSON data files
    'pages' => resource_path('views/pages'),

    // Path to section Blade templates
    'sections' => resource_path('views/sections'),

    // Path to theme block Blade templates
    'blocks' => resource_path('views/blocks'),

    // Middleware applied to editor routes
    'middleware' => ['web'],

    // Filesystem disk for asset uploads
    'disk' => 'public',

    // Directory within the disk for uploaded assets
    'asset_directory' => 'pagebuilder',

    // Reserved slugs that cannot be used for dynamic pages
    'preserved_pages' => ['home'],
];
```

### Publish Other Resources

```bash
# Blade views (editor layout, built-in views)
php artisan vendor:publish --tag=pagebuilder-views

# Database migrations
php artisan vendor:publish --tag=pagebuilder-migrations

# Editor frontend assets (React SPA)
php artisan vendor:publish --tag=pagebuilder-assets
```

### Run Migrations

```bash
php artisan migrate
```

---

## Creating Sections

Sections are the top-level building blocks of a page. Each section is a Blade view that declares its schema using the `@schema()` directive.

### 1. Create the Blade file

Place section templates in the configured sections directory (default: `resources/views/sections/`).

```blade
{{-- resources/views/sections/hero.blade.php --}}
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title',    'type' => 'text',  'label' => 'Title',    'default' => 'Welcome'],
        ['id' => 'subtitle', 'type' => 'text',  'label' => 'Subtitle', 'default' => ''],
        ['id' => 'bg_color', 'type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
    ],
    'blocks' => [
        ['type' => 'row'],
        ['type' => '@theme'],
    ],
    'presets' => [
        ['name' => 'Hero'],
        ['name' => 'Hero with Row', 'blocks' => [
            ['type' => 'row', 'settings' => ['columns' => '2']],
        ]],
    ],
])

<section {!! $section->editorAttributes() !!}
    style="background-color: {{ $section->settings->bg_color }}">
    <div class="container mx-auto px-4">
        <h1>{{ $section->settings->title }}</h1>
        <p>{{ $section->settings->subtitle }}</p>
        @blocks($section)
    </div>
</section>
```

### 2. Understanding the `@schema()` array

| Key          | Type   | Description                                                  |
| ------------ | ------ | ------------------------------------------------------------ |
| `name`       | string | **Required.** Human-readable name shown in the editor        |
| `settings`   | array  | Setting definitions with `id`, `type`, `label`, `default`    |
| `blocks`     | array  | Allowed child block types (inline definitions or theme refs) |
| `presets`    | array  | Pre-configured templates shown in the "Add section" picker   |
| `max_blocks` | int    | Maximum number of child blocks allowed                       |

### 3. Section template API

| Property / Method              | Description                                                |
| ------------------------------ | ---------------------------------------------------------- |
| `$section->id`                 | Unique instance ID                                         |
| `$section->type`               | Section type identifier (matches filename)                 |
| `$section->name`               | Human-readable name from schema                            |
| `$section->settings->key`      | Typed setting access with automatic defaults               |
| `$section->blocks`             | `BlockCollection` of hydrated top-level blocks             |
| `$section->editorAttributes()` | Editor `data-*` attributes (empty string when not editing) |
| `@blocks($section)`            | Renders all top-level blocks                               |

---

## Creating Blocks

Blocks are reusable components that live inside sections (or inside other blocks). Block Blade files live in the configured blocks directory (default: `resources/views/blocks/`).

### Theme Blocks

Theme blocks are registered globally and can be referenced by any section that declares `['type' => '@theme']` in its `blocks` array.

```blade
{{-- resources/views/blocks/row.blade.php --}}
@schema([
    'name' => 'Row',
    'settings' => [
        [
            'id' => 'columns',
            'type' => 'select',
            'label' => 'Columns',
            'default' => '2',
            'options' => [
                [
                    'value' => '1',
                    'label' => '1 Column',
                ],
                ['value' => '2', 'label' => '2 Columns'],
                ['value' => '3', 'label' => '3 Columns'],
            ],
        ],
        [
            'id' => 'gap',
            'type' => 'select',
            'label' => 'Gap',
            'default' => 'md',
            'options' => [
                [
                    'value' => 'none',
                    'label' => 'None',
                ],
                ['value' => 'sm', 'label' => 'Small'],
                ['value' => 'md', 'label' => 'Medium'],
                ['value' => 'lg', 'label' => 'Large'],
            ],
        ],
    ],
    'blocks' => [
        [
            'type' => 'column',
            'name' => 'Column',
        ],
    ],
    'presets' => [
        [
            'name' => 'Two Columns',
            'settings' => ['columns' => '2'],
            'blocks' => [
                [
                    'type' => 'column',
                ],
                ['type' => 'column'],
            ],
        ],
        [
            'name' => 'Three Columns',
            'settings' => ['columns' => '3'],
            'blocks' => [
                [
                    'type' => 'column',
                ],
                ['type' => 'column'],
                ['type' => 'column'],
            ],
        ],
    ],
])

<div {!! $block->editorAttributes() !!}
    class="grid grid-cols-{{ $block->settings->columns }} gap-{{ $block->settings->gap }}">
    @blocks($block)
</div>
```

```blade
{{-- resources/views/blocks/column.blade.php --}}
@schema([
    'name'     => 'Column',
    'settings' => [
        ['id' => 'padding', 'type' => 'select', 'label' => 'Padding', 'default' => 'none',
         'options' => [
             ['value' => 'none', 'label' => 'None'],
             ['value' => 'sm', 'label' => 'Small'],
             ['value' => 'md', 'label' => 'Medium'],
             ['value' => 'lg', 'label' => 'Large'],
         ]],
    ],
    'blocks' => [
        ['type' => '@theme'],
    ],
])

<div {!! $block->editorAttributes() !!} class="p-{{ $block->settings->padding }}">
    @blocks($block)
</div>
```

### Block template API

| Property / Method            | Description                                      |
| ---------------------------- | ------------------------------------------------ |
| `$block->id`                 | Unique block instance ID                         |
| `$block->type`               | Block type identifier (matches filename)         |
| `$block->settings->key`      | Typed setting access with defaults               |
| `$block->blocks`             | `BlockCollection` of nested child blocks         |
| `$block->editorAttributes()` | Editor `data-*` attributes                       |
| `$section`                   | Parent section (always available in block views) |
| `@blocks($block)`            | Renders child blocks of this container           |

### Block Detection: Local vs Theme Reference

In `@schema` `blocks` arrays, entries are detected as either **local definitions** or **theme-block references**:

| Entry                                                           | Type             | Detection                                          |
| --------------------------------------------------------------- | ---------------- | -------------------------------------------------- |
| `['type' => 'column']`                                          | Theme reference  | Only has `type` key → resolved from block registry |
| `['type' => '@theme']`                                          | Wildcard         | Accepts any registered theme block                 |
| `['type' => 'column', 'name' => 'Column', 'settings' => [...]]` | Local definition | Has extra keys → used as-is                        |

---

## Page JSON Structure

Pages are stored as JSON files in the configured pages directory. Each page contains sections, their settings, nested blocks, and render order.

```json
{
  "title": "Home",
  "meta": {
    "description": "Welcome to our site"
  },
  "sections": {
    "hero-1": {
      "type": "hero",
      "settings": {
        "title": "Welcome",
        "subtitle": "Build amazing pages",
        "bg_color": "#f0f0f0"
      },
      "blocks": {
        "row-1": {
          "type": "row",
          "settings": { "columns": "2", "gap": "md" },
          "blocks": {
            "col-left": {
              "type": "column",
              "settings": { "padding": "md" },
              "blocks": {}
            },
            "col-right": {
              "type": "column",
              "settings": { "padding": "md" },
              "blocks": {}
            }
          },
          "order": ["col-left", "col-right"]
        }
      },
      "order": ["row-1"]
    }
  },
  "order": ["hero-1"]
}
```

---

## Rendering Pages

### In Controllers

```php
use Coderstm\PageBuilder\Services\PageRenderer;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRenderer $pageRenderer,
    ) {}

    public function show(string $slug)
    {
        $html = $this->pageRenderer->render($slug);

        if ($html === null) {
            abort(404);
        }

        return view('layouts.page', ['content' => $html]);
    }
}
```

### Programmatic Page Rendering

```php
use Coderstm\PageBuilder\Services\PageRenderer;

$renderer = app(PageRenderer::class);

// Render from slug (loads JSON from disk)
$html = $renderer->render('home');

// Render from raw data
$html = $renderer->renderPage([
    'sections' => [...],
    'order' => [...],
]);

// Render with editor mode (adds data-editor-* attributes)
$html = $renderer->render('home', editor: true);
```

---

## Registering Additional Paths

You can register additional directories for section and block discovery:

```php
use Coderstm\PageBuilder\Facades\Section;
use Coderstm\PageBuilder\Facades\Block;

// In a service provider's boot() method
Section::add(resource_path('views/custom-sections'));
Block::add(resource_path('views/custom-blocks'));
```

### Manual Registration

Register a section or block programmatically without a Blade file:

```php
use Coderstm\PageBuilder\Facades\Section;
use Coderstm\PageBuilder\Schema\SectionSchema;

Section::register('custom-hero', new SectionSchema([
    'name' => 'Custom Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Hello'],
    ],
]), 'my-views::sections.custom-hero');
```

---

## Setting Types

The `@schema` settings array supports these built-in types:

| Type               | Description                      | Extra Keys                  |
| ------------------ | -------------------------------- | --------------------------- |
| `text`             | Single-line text input           | —                           |
| `textarea`         | Multi-line text input            | —                           |
| `richtext`         | Rich text editor (multi-line)    | —                           |
| `inline_richtext`  | Rich text editor (single-line)   | —                           |
| `select`           | Dropdown select                  | `options: [{value, label}]` |
| `radio`            | Radio buttons                    | `options: [{value, label}]` |
| `checkbox`         | Boolean toggle                   | —                           |
| `range`            | Numeric slider                   | `min`, `max`, `step`        |
| `number`           | Number input                     | `min`, `max`, `step`        |
| `color`            | Color picker (hex)               | —                           |
| `color_background` | CSS background (gradients)       | —                           |
| `image_picker`     | Media library selector           | —                           |
| `url`              | Link/URL input                   | —                           |
| `video_url`        | YouTube/Vimeo URL                | —                           |
| `icon_fa`          | FontAwesome icon picker          | —                           |
| `icon_md`          | Material Design icon picker      | —                           |
| `text_alignment`   | Left/Center/Right segmented ctrl | —                           |
| `html`             | Raw HTML code editor             | —                           |
| `blade`            | Blade template code editor       | —                           |
| `header`           | Sidebar section divider          | `content`                   |
| `paragraph`        | Sidebar informational text       | `content`                   |
| `external`         | Dynamic API-driven selector      | —                           |

---

## Editor

### Accessing the Editor

The editor is available at:

```
GET /pagebuilder/{slug?}
```

Protect it with authentication middleware in your config:

```php
// config/pagebuilder.php
'middleware' => ['web', 'auth'],
```

### Editor API Endpoints

| Method | URL                           | Description           |
| ------ | ----------------------------- | --------------------- |
| `GET`  | `/pagebuilder/pages`          | List all pages        |
| `GET`  | `/pagebuilder/page/{slug}`    | Get page JSON         |
| `POST` | `/pagebuilder/render-section` | Live-render a section |
| `POST` | `/pagebuilder/save-page`      | Save page JSON        |
| `GET`  | `/pagebuilder/assets`         | List uploaded assets  |
| `POST` | `/pagebuilder/assets/upload`  | Upload an asset       |

### Editor Helpers

```php
// Check if editor mode is active
pb_editor(); // Returns bool

// In Blade templates
@if(pb_editor())
    {{-- Editor-only content --}}
@endif
```

---

## Custom Asset Providers

By default the editor stores uploaded assets through the built-in Laravel provider (`storage/app/public/pagebuilder`). You can replace it with any storage backend — S3, Cloudflare R2, Cloudinary, DigitalOcean Spaces — by passing a custom provider to `PageBuilder.init()`.

### Provider interface

A provider is a plain JavaScript object with two async methods:

```js
const myProvider = {
  // Return a paginated list of assets
  async list({ page = 1, search = "" } = {}) {
    // Must return: { data: Asset[], pagination: { page, per_page, total } }
  },

  // Upload a File object, return the stored asset
  async upload(file) {
    // Must return: { id, name, url, thumbnail, size, type }
  },
};
```

The `url` field is what gets stored in page JSON and rendered in Blade — it must be a publicly accessible URL.

### Registering the provider

```html
<script src="/vendor/pagebuilder/app.js"></script>
<script>
  PageBuilder.init({
    baseUrl: "/pagebuilder",
    assets: {
      provider: myProvider,
    },
  });
</script>
```

### AWS S3 / DigitalOcean Spaces / Cloudflare R2

Keep uploads server-side through a thin Laravel proxy controller that writes to S3 using `Storage::disk('s3')`:

```js
const s3Provider = {
  async list({ page = 1, search = "" } = {}) {
    const q = new URLSearchParams({ page, q: search });
    const res = await fetch(`/api/pagebuilder/assets?${q}`);
    if (!res.ok) throw new Error("Failed to fetch assets");
    return res.json();
  },
  async upload(file) {
    const body = new FormData();
    body.append("file", file);
    const res = await fetch("/api/pagebuilder/assets/upload", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN":
          document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? "",
      },
      body,
    });
    if (!res.ok) throw new Error("Upload failed");
    return res.json();
  },
};
```

For Spaces/R2, configure the S3-compatible endpoint in `.env` — no JS changes required:

```env
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com   # Spaces
# or
AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com  # R2
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Cloudinary (direct browser upload)

```js
const cloudinaryProvider = {
  async list({ page = 1, search = "" } = {}) {
    const q = new URLSearchParams({ page, q: search });
    const res = await fetch(`/api/pagebuilder/cloudinary/assets?${q}`);
    if (!res.ok) throw new Error("Failed to fetch assets");
    return res.json();
  },
  async upload(file) {
    // Get a signed upload preset from your Laravel backend
    const sigRes = await fetch("/api/pagebuilder/cloudinary/sign", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN":
          document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? "",
      },
      body: JSON.stringify({ filename: file.name }),
    });
    const { signature, timestamp, cloudName, apiKey, folder } =
      await sigRes.json();

    const body = new FormData();
    body.append("file", file);
    body.append("api_key", apiKey);
    body.append("timestamp", timestamp);
    body.append("signature", signature);
    body.append("folder", folder);

    const up = await fetch(
      `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
      { method: "POST", body },
    );
    if (!up.ok) throw new Error("Cloudinary upload failed");
    const d = await up.json();

    return {
      id: d.public_id,
      name: d.original_filename,
      url: d.secure_url,
      thumbnail: d.secure_url.replace(
        "/upload/",
        "/upload/w_200,h_200,c_fill/",
      ),
      size: d.bytes,
      type: `${d.resource_type}/${d.format}`,
    };
  },
};
```

For the full provider contract and additional examples, see the [Developer Documentation](docs/index.md).

---

## Blade Directives

| Directive           | Description                                                       |
| ------------------- | ----------------------------------------------------------------- |
| `@blocks($section)` | Renders all top-level blocks of a section                         |
| `@blocks($block)`   | Renders child blocks inside a container block                     |
| `@schema([...])`    | Declares schema (no-op at render time, extracted at registration) |
| `@pbEditorClass`    | Outputs CSS class when editor mode is active                      |

---

## Architecture Reference

### Key Classes

| Class             | Responsibility                                                    |
| ----------------- | ----------------------------------------------------------------- |
| `SectionRegistry` | Discovers section Blade files, extracts schemas, provides lookup  |
| `BlockRegistry`   | Discovers block Blade files, extracts schemas, provides lookup    |
| `Renderer`        | Core rendering engine: hydrates JSON → objects, renders via Blade |
| `PageRenderer`    | Loads page JSON, renders all enabled sections in order            |
| `PageStorage`     | Reads/writes page JSON files to disk                              |
| `PagePublisher`   | Compiles pages into static Blade files                            |
| `PageBuilder`     | Static API for editor mode, CSS/JS asset URLs                     |

---

## Reporting Issues

When reporting bugs, please include:

- PHP and Laravel versions
- Package version
- Steps to reproduce
- Expected vs actual behavior
- Relevant error messages or logs

---

## Layout Sections

Pages can define a `layout` key for per-page overrides of structural slots (header, footer) that live **outside** the main `@yield('content')` block in your Blade layout.

```json
{
    "sections": { "..." },
    "order": ["hero"],
    "layout": {
        "type": "page",
        "sections": {
            "header": {
                "type": "site-header",
                "settings": { "sticky": true },
                "blocks": {},
                "order": [],
                "disabled": false
            },
            "footer": {
                "type": "site-footer",
                "settings": {},
                "blocks": {},
                "order": [],
                "disabled": false
            }
        }
    }
}
```

Render layout sections in your Blade layout file using `@sections()`:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<body class="@pbEditorClass">
    @sections('header')
    @yield('content')
    @sections('footer')
</body>
```

Layout sections are **non-sortable** — their position is determined by the Blade layout. In the editor they appear as fixed rows above and below the sortable page section list.

**Rules:**

- Keys that match `"header"` or carry `position: "top"` render in the top zone; everything else goes to the bottom zone.
- `disabled: true` causes `@sections()` to return an empty string for that slot.
- `_name` overrides the schema display name in the editor (same as page sections).

---

## Theme Integration

The package integrates with [qirolab/laravel-themer](https://github.com/qirolab/laravel-themer) for multi-theme support.

### Register Theme Sections and Blocks

```php
use Coderstm\PageBuilder\Facades\Section;
use Coderstm\PageBuilder\Facades\Block;

// In a ThemeServiceProvider or AppServiceProvider boot() method:
Section::add(base_path('themes/my-theme/views/sections'));
Block::add(base_path('themes/my-theme/views/blocks'));
```

### Global Theme Settings

Define global design tokens (colors, fonts, spacing) in `config/pagebuilder.php`:

```php
'theme_settings_schema' => [
    [
        'id'      => 'primary_color',
        'type'    => 'color',
        'label'   => 'Primary Color',
        'default' => '#3B82F6',
    ],
    [
        'id'      => 'font_family',
        'type'    => 'select',
        'label'   => 'Font Family',
        'default' => 'sans',
        'options' => [
            ['value' => 'sans',  'label' => 'Sans Serif'],
            ['value' => 'serif', 'label' => 'Serif'],
            ['value' => 'mono',  'label' => 'Monospace'],
        ],
    ],
],
```

Access theme settings in Blade views via the globally shared `$theme` variable:

```blade
<style>
    :root {
        --primary: {{ $theme->primary_color ?? '#3B82F6' }};
    }
</style>
```

`$theme` is a `ThemeSettings` instance shared with all Blade views. Use property-style access (`$theme->key`) or the `get()` helper with a default (`$theme->get('key', 'default')`).

### Theme Middleware

You can use the provided `ThemeMiddleware` to automatically apply themes based on route parameters or session data.

```php
// bootstrap/app.php (Laravel 11+)
$middleware->alias([
    'theme' => \Coderstm\PageBuilder\Http\Middleware\ThemeMiddleware::class,
]);

// routes/web.php
Route::get('/shop/{theme_slug}', function () {
    // ...
})->middleware('theme:theme_slug');
```

---

## Artisan Commands

```bash
# Regenerate the page registry cache
# Run this after adding, renaming, or removing page JSON files
php artisan pages:regenerate

# Symlink theme assets into the public directory
php artisan theme:link
```

---

## License

This package is released under a **Non-Commercial Open Source License**.

- Free to use, modify, and distribute for **non-commercial purposes**.
- **Commercial use is not permitted** without a separate license agreement.
- Contact [hello@dipaksarkar.in](mailto:hello@dipaksarkar.in) for commercial licensing.

See [LICENSE.md](LICENSE.md) for the full license text.
