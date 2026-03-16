# Laravel Page Builder — Core Architecture

**Package:** `coderstm/laravel-page-builder`
**PHP:** 8.2+ | **Laravel:** 11.x / 12.x

---

## Overview

The Page Builder is a multi-theme, JSON-driven page composition system inspired by Shopify's theme architecture. Pages are assembled from **sections** (structural containers) and **blocks** (reusable content elements), all driven by a **Page JSON** document stored per page.

---

## Five-Layer Architecture

Dependencies flow downward only. Lower layers never import from higher layers.

```
Schema → Registry → Components (Runtime) → Rendering → Services/Controllers
```

### Layer 1 — Schema (`src/Schema/`)

**Purpose:** Immutable value objects that describe what a section or block _can_ look like.

| Class           | Responsibility                                                       |
| --------------- | -------------------------------------------------------------------- |
| `SectionSchema` | Defines a section: name, settings, allowed blocks, presets, limits   |
| `BlockSchema`   | Defines a block: type, name, settings, allowed child blocks, presets |
| `SettingSchema` | Defines one setting: id, type, label, default value, options         |

Rules:

- All properties are `readonly`.
- Schema objects are **never mutated** after construction.
- Constructed from raw arrays extracted from Blade `@schema()` directives.

### Layer 2 — Registry (`src/Registry/`)

**Purpose:** Discovers, stores, and provides typed schema objects.

| Class             | Responsibility                                                                        |
| ----------------- | ------------------------------------------------------------------------------------- |
| `SectionRegistry` | Scans `resources/views/sections/`, registers `SectionSchema` objects                  |
| `BlockRegistry`   | Scans `resources/views/blocks/`, registers `BlockSchema` objects                      |
| `SchemaExtractor` | Parses `@schema([...])` directives from Blade files using balanced-bracket extraction |
| `LayoutParser`    | Reads default layout zones (header/footer) from layout Blade                          |

Rules:

- Registries are singletons, lazy-loaded from Blade file scanning.
- **Last registration wins** — themes can shadow built-in schemas.
- `SectionRegistry::get('hero')` → returns `SectionSchema`.
- `BlockRegistry::get('row')` → returns `BlockSchema`.

### Layer 3 — Components (`src/Components/`, `src/Collections/`)

**Purpose:** Runtime instances hydrated from page JSON using schema defaults.

| Class               | Responsibility                                                                   |
| ------------------- | -------------------------------------------------------------------------------- |
| `Section`           | Runtime section: `id`, `type`, `settings`, `blocks`, `editorAttributes()`        |
| `Block`             | Runtime block: `id`, `type`, `settings`, `blocks` (nested), `editorAttributes()` |
| `Settings`          | Schema-aware settings bag with magic `__get`, `ArrayAccess`, default resolution  |
| `BlockCollection`   | Ordered, iterable collection of `Block` instances                                |
| `SectionCollection` | Ordered collection of `Section` instances with `render()` and `enabled()`        |

Rules:

- Components are hydrated by `Renderer`, never instantiated directly.
- `Settings` resolves defaults from schema when a key has no stored value.
- `Block` always has a `BlockCollection $blocks` — leaf blocks have an empty one.

### Layer 4 — Rendering (`src/Rendering/`)

**Purpose:** Converts runtime objects into HTML via Blade views.

| Class              | Key Methods                                                                                             |
| ------------------ | ------------------------------------------------------------------------------------------------------- |
| `Renderer`         | `renderSection`, `renderBlock`, `renderBlocks`, `renderBlockChildren`, `hydrateSection`, `hydrateBlock` |
| `EditorAttributes` | `forSection`, `forBlock`, `autoInjectLiveText`                                                          |
| `BladeDirectives`  | Registers `@blocks`, `@schema`, `@sections`, `@pbEditorClass`                                           |

Rules:

- ALL rendering goes through `Renderer` — never render sections or blocks directly from views.
- In editor mode, `autoInjectLiveText` injects `data-live-text-setting` on string settings automatically.
- `@blocks($section)` → renders all top-level blocks; `@blocks($block)` → renders nested child blocks.

### Layer 5 — Services (`src/Services/`)

**Purpose:** High-level orchestrators for page loading, rendering, and persistence.

| Class                     | Responsibility                                                                |
| ------------------------- | ----------------------------------------------------------------------------- |
| `PageRenderer`            | Loads page JSON → hydrates all sections → renders complete HTML; applies wrapper |
| `PageStorage`             | JSON file I/O for page data (reads/writes from `config('pagebuilder.pages')`) |
| `TemplateStorage`         | JSON file I/O for template data (theme-aware, `config('pagebuilder.templates')`) |
| `TemplateVariableResolver`| Resolves `{{ $page->attr }}` placeholders in template section settings        |
| `WrapperParser`           | Parses CSS-selector wrapper strings (e.g. `div#id.class`) into HTML elements  |
| `PageRegistry`            | Cached page manifest (`bootstrap/cache/pagebuilder_pages.php`)                |
| `PageService`             | Route registration + page resolution (Blade → JSON → template → 404)         |
| `ThemeSettings`           | Global theme settings persistence (JSON file)                                 |
| `Theme`                   | Active theme management wrapper                                               |

---

## Dependency Injection Rules

Always inject via constructor — never instantiate directly.

```php
// WRONG
$renderer = new Renderer();

// CORRECT
public function __construct(
    private readonly Renderer $renderer,
) {}
```

---

## Facades

```php
use Coderstm\PageBuilder\Facades\Section;
use Coderstm\PageBuilder\Facades\Block;
use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Facades\Theme;

Section::get('hero');       // → SectionSchema
Block::get('row');          // → BlockSchema
Page::find('home');         // → Page model
```

---

## Helper Functions

```php
pb_editor();    // bool — true when editor mode is active
theme();        // string — returns active theme name
theme_vite();   // Vite — returns Vite asset loader for the active theme
```

---

## Configuration (`config/pagebuilder.php`)

```php
return [
    'pages'                 => resource_path('views/pages'),          // Page JSON files dir
    'sections'              => resource_path('views/sections'),        // Section Blade dir
    'blocks'                => resource_path('views/blocks'),          // Block Blade dir
    'templates'             => resource_path('views/templates'),       // Template JSON files dir
    'middleware'            => ['web'],                                // Route middleware
    'disk'                  => 'public',                               // Storage disk for assets
    'asset_directory'       => 'pagebuilder',                          // Sub-dir on disk
    'theme_settings_schema' => [],                                     // Global theme settings
    'theme_settings_path'   => resource_path('theme-settings.json'),   // Theme settings file
];
```

---

## Artisan Commands

```bash
php artisan pages:regenerate   # Regenerate the page registry cache
php artisan theme:link                # Symlink theme public assets
```

---

## HTTP Routes

| Method | URI                           | Controller                     | Purpose                        |
| ------ | ----------------------------- | ------------------------------ | ------------------------------ |
| GET    | `/pagebuilder/{slug?}`        | `PageBuilderController@editor` | Load React editor SPA          |
| GET    | `/pagebuilder/pages`          | `PageBuilderController`        | List all pages                 |
| GET    | `/pagebuilder/page/{slug}`    | `PageBuilderController`        | Get page JSON + default layout |
| POST   | `/pagebuilder/render-section` | `PageBuilderController`        | Live preview section render    |
| POST   | `/pagebuilder/render-block`   | `PageBuilderController`        | Live preview block render      |
| POST   | `/pagebuilder/save-page`      | `PageBuilderController`        | Persist page JSON              |
| GET    | `/pagebuilder/theme-settings` | `PageBuilderController`        | Read theme settings            |
| POST   | `/pagebuilder/theme-settings` | `PageBuilderController`        | Save theme settings            |
| GET    | `/pagebuilder/assets`         | `AssetController`              | List media library             |
| POST   | `/pagebuilder/assets/upload`  | `AssetController`              | Upload media asset             |
| GET    | `/{slug}`                     | `WebPageController@pages`      | Render published page          |

---

## Page Model

```php
// src/Models/Page.php
$fillable = ['parent', 'title', 'slug', 'meta_title', 'meta_keywords',
             'meta_description', 'is_active', 'template', 'metadata', 'content'];
$casts    = ['is_active' => 'boolean', 'metadata' => 'json'];
```

---

## Code Style Rules

```php
declare(strict_types=1);

namespace Coderstm\PageBuilder\Schema;

final class SectionSchema
{
    public readonly string $name;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? '';
    }
}
```

- `declare(strict_types=1)` on every file.
- All properties typed with explicit visibility.
- All methods have return types.
- `readonly` for value objects and DTOs.
- PHP 8.2+ features: `readonly`, enums, named arguments, match expressions.
- PSR-12 formatting.
