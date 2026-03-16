---
name: page-builder-development
description: Laravel Page Builder development. Activate when creating or modifying sections, blocks, themes, page JSON layouts, schema definitions, registry classes, rendering pipeline, editor integration, or any feature of the coderstm/laravel-page-builder package.
---

# Laravel Page Builder Development

A multi-theme, JSON-driven page composition system for Laravel. Pages are assembled from **sections** (primary structural units) that contain **blocks** (reusable content elements), all driven by a Page JSON document. The editor reads schemas defined inline in Blade files via `@schema()` — no separate schema files needed.

## Documentation

Use `search-docs` for detailed Page Builder patterns and documentation.

## Architecture

Five layers — dependencies flow **downward only**:

- **Schema** (`src/Schema/`) — Immutable `readonly` value objects: `SectionSchema`, `BlockSchema`, `SettingSchema`
- **Registry** (`src/Registry/`) — `SectionRegistry`, `BlockRegistry` discover schemas from Blade `@schema()` directives
- **Components** (`src/Components/`, `src/Collections/`) — Runtime `Section`, `Block`, `Settings`, `BlockCollection`, `SectionCollection`
- **Rendering** (`src/Rendering/`) — `Renderer` hydrates JSON → objects and renders via Blade
- **Services** (`src/Services/`) — `PageRenderer`, `PageStorage`, `PageRegistry`, `ThemeSettings`

> Use `search-docs` for layer boundaries and dependency injection patterns.

## Usage

- **Facades**: `Section::get('hero')`, `Block::get('row')`, `Page::findBySlug('home')`, `Theme::active()`
- **Config**: `config/pagebuilder.php` — keys: `pages`, `sections`, `blocks`, `disk`, `theme_settings_schema`, `theme_settings_path`
- **Helpers**: `pb_editor()` — bool editor mode; `theme()` — active theme name; `theme_vite()` — Vite asset loader
- **Artisan**: `php artisan pages:regenerate` to rebuild the page registry cache
- **Routes**: All editor API endpoints are under `/pagebuilder/*`; published pages are served at `/{slug}`

## Blade Directives

| Directive | Purpose |
|---|---|
| `@schema([...])` | Schema definition; no-op at render time, extracted at registration |
| `@blocks($section)` | Renders all top-level blocks of a section |
| `@blocks($block)` | Renders all child blocks of a container block |
| `@sections('header')` | Renders a layout slot (header / footer) |
| `@pbEditorClass` | Adds `js pb-design-mode` to `<html>` in editor mode |
| `@pbEditorScripts` | Injects editor interaction scripts in editor mode |

> Never call `@blocks()` outside a Blade view. Never call the renderer directly from a template.

---

## Sections

A section is the **primary structural unit** of a page. It is a Blade file identified by its filename (without `.blade.php`), auto-discovered by `SectionRegistry`.

### Minimal section

```blade
{{-- resources/views/sections/hero.blade.php --}}

@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title',    'type' => 'text', 'label' => 'Title',    'default' => 'Welcome'],
        ['id' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle', 'default' => ''],
    ],
])

<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
    <p>{{ $section->settings->subtitle }}</p>
</section>
```

### Section with blocks

```blade
@schema([
    'name' => 'Section',
    'settings' => [
        ['id' => 'max_width',        'type' => 'select', 'label' => 'Max Width',       'default' => '7xl',
         'options' => [['value' => 'full', 'label' => 'Full'], ['value' => '7xl', 'label' => '7xl']]],
        ['id' => 'padding_top',      'type' => 'select', 'label' => 'Padding Top',     'default' => 'md'],
        ['id' => 'padding_bottom',   'type' => 'select', 'label' => 'Padding Bottom',  'default' => 'md'],
        ['id' => 'background_color', 'type' => 'color',  'label' => 'Background',      'default' => ''],
    ],
    'blocks' => [
        ['type' => 'row'],     // bare reference → resolved from BlockRegistry
        ['type' => '@theme'],  // wildcard — accepts any registered theme block
    ],
    'presets' => [
        ['name' => 'Section'],
    ],
])

<section {!! $section->editorAttributes() !!}>
    <div class="mx-auto max-w-{{ $section->settings->max_width }}">
        @blocks($section)
    </div>
</section>
```

### Section template API (`$section`)

```blade
$section->id                              {{-- unique instance ID --}}
$section->type                            {{-- schema type (e.g. "hero") --}}
$section->settings->title                 {{-- magic __get with default resolution --}}
$section->settings->get('title', 'fallback')  {{-- explicit fallback --}}
$section->settings->all()                 {{-- all settings as key→value array --}}
$section->blocks                          {{-- BlockCollection of top-level blocks --}}
$section->blocks->count()                 {{-- number of blocks --}}
$section->editorAttributes()              {{-- outputs editor data attributes or "" --}}
```

### `@schema()` — full section structure

```php
@schema([
    'name'       => 'Hero',      // Display name in editor
    'tag'        => 'section',   // HTML tag hint (optional)
    'settings'   => [...],       // Array of setting definitions
    'blocks'     => [...],       // Allowed block definitions (bare refs or local)
    'max_blocks' => 10,          // Maximum number of blocks (optional)
    'presets'    => [...],       // Quick-add presets (optional)
    'limit'      => 1,           // Max instances on a page (optional)
])
```

---

## Blocks

A block is a reusable content element that lives inside a section or inside another block. Blocks are Blade files at `resources/views/blocks/{type}.blade.php`, auto-registered by `BlockRegistry`. Nesting is supported to any depth.

### Simple (leaf) block

```blade
{{-- resources/views/blocks/button.blade.php --}}

@schema([
    'name' => 'Button',
    'settings' => [
        ['id' => 'label', 'type' => 'text',   'label' => 'Label', 'default' => 'Click Me'],
        ['id' => 'url',   'type' => 'url',    'label' => 'URL',   'default' => '#'],
        ['id' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'primary',
         'options' => [
             ['value' => 'primary',   'label' => 'Primary'],
             ['value' => 'secondary', 'label' => 'Secondary'],
             ['value' => 'outline',   'label' => 'Outline'],
         ]],
    ],
])

<a {!! $block->editorAttributes() !!}
   href="{{ $block->settings->url }}"
   class="btn btn-{{ $block->settings->style }}">
    {{ $block->settings->label }}
</a>
```

### Container block (accepts child blocks)

```blade
{{-- resources/views/blocks/row.blade.php --}}

@schema([
    'name' => 'Row',
    'settings' => [
        ['id' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '2',
         'options' => [
             ['value' => '1', 'label' => '1 Column'],
             ['value' => '2', 'label' => '2 Columns'],
             ['value' => '3', 'label' => '3 Columns'],
         ]],
    ],
    'blocks' => [
        ['type' => 'column'],   // bare reference → resolved from BlockRegistry
    ],
    'presets' => [
        ['name' => 'Two Columns', 'settings' => ['columns' => '2'],
         'blocks' => [['type' => 'column'], ['type' => 'column']]],
    ],
])

<div {!! $block->editorAttributes() !!} class="grid grid-cols-{{ $block->settings->columns }}">
    @blocks($block)    {{-- renders child blocks --}}
</div>
```

### Block template API (`$block`)

```blade
$block->id                          {{-- unique instance ID --}}
$block->type                        {{-- block type (e.g. "row", "column") --}}
$block->settings->label             {{-- magic __get with default resolution --}}
$block->settings->get('label', 'fallback')
$block->settings->all()             {{-- all settings as key→value array --}}
$block->blocks                      {{-- BlockCollection of child blocks --}}
$block->blocks->count()
$block->blocks->ofType('column')    {{-- filter children by type --}}
$block->editorAttributes()

$section                            {{-- parent Section (may be null for isolated renders) --}}
```

### Local vs theme block references

| Entry in `blocks` array | Detected as | Resolved via |
|---|---|---|
| `['type' => 'row']` — only `type` key | Theme reference | `BlockRegistry::get('row')` |
| `['type' => '@theme']` | Wildcard — any theme block | `BlockRegistry::all()` |
| `['type' => 'item', 'name' => '...']` — extra keys | Local definition | Used as-is; no registry lookup |

> Never add `name` or `settings` to a bare theme reference — it changes the lookup behaviour.

---

## Setting Types

| Type | Description | Extra fields |
|---|---|---|
| `text` | Single-line text input | — |
| `textarea` | Multi-line text | — |
| `richtext` | TipTap rich-text editor | — |
| `number` | Numeric input | `min`, `max`, `step` |
| `range` | Slider | `min`, `max`, `step` |
| `select` | Dropdown | `options: [{value, label}]` |
| `radio` | Radio group | `options: [{value, label}]` |
| `checkbox` | Boolean toggle | — |
| `color` | Color picker | — |
| `image_picker` | Media library image | — |
| `alignment` | Alignment picker | — |
| `icon_picker` | Icon selector | — |
| `url` | URL input | — |

### Setting definition shape

```php
[
    'id'      => 'title',        // Required. Key used as $section->settings->title
    'type'    => 'text',         // Required. Input type (see table above)
    'label'   => 'Title',        // Required. Editor label
    'default' => 'Welcome',      // Optional. Fallback when not set in page JSON
    'options' => [               // Required for select/radio
        ['value' => 'sm', 'label' => 'Small'],
        ['value' => 'lg', 'label' => 'Large'],
    ],
    'min'  => 0,   // For number/range
    'max'  => 100, // For number/range
    'step' => 1,   // For number/range
]
```

---

## Page JSON

Every page is backed by a JSON document at `config('pagebuilder.pages')/{slug}.json`.

### Structure

```json
{
    "sections": {
        "hero": {
            "type": "hero",
            "settings": { "title": "Welcome" },
            "blocks": {
                "row1": {
                    "type": "row",
                    "settings": { "columns": "2" },
                    "blocks": {
                        "col-left":  { "type": "column", "settings": {}, "blocks": {}, "order": [] },
                        "col-right": { "type": "column", "settings": {}, "blocks": {}, "order": [] }
                    },
                    "order": ["col-left", "col-right"]
                }
            },
            "order": ["row1"]
        },
        "cta": {
            "type": "cta",
            "settings": { "title": "Get Started" },
            "blocks": {},
            "order": []
        }
    },
    "order": ["hero", "cta"],
    "layout": {
        "type": "page",
        "sections": {
            "header": {
                "type": "site-header",
                "settings": { "sticky": true },
                "blocks": {},
                "order": []
            },
            "footer": {
                "type": "site-footer",
                "settings": {},
                "blocks": {},
                "order": []
            }
        }
    }
}
```

### Field reference

| Field | Required | Description |
|---|---|---|
| `sections` | yes | Map of section instances keyed by unique ID |
| `order` | yes | Render order of section IDs |
| `layout` | no | Per-page layout slot overrides (header/footer) |
| section `type` | yes | Matches a registered `SectionSchema` (Blade filename) |
| section `settings` | yes | Key→value overrides; missing keys fall back to schema defaults |
| section `blocks` | yes | Map of block instances keyed by unique ID |
| section `order` | yes | Render order of block IDs within this section |
| `disabled` | no | `true` hides section/block from rendered output |
| `_name` | no | Custom display label in editor |

> Every `blocks` map requires a matching `order` array. Missing `order` is a common bug.

### Settings resolution priority

1. Value stored in page JSON for this instance
2. Default from `SettingSchema` (the `default` key in `@schema`)
3. `null` if no default exists

---

## Setup Workflows

### Create a New Section

```
- [ ] Create resources/views/sections/{type}.blade.php
- [ ] Add @schema([...]) with name, settings, blocks, presets
- [ ] Add {!! $section->editorAttributes() !!} to the root HTML element
- [ ] Use $section->settings->key for setting values
- [ ] Use @blocks($section) to render child blocks
- [ ] Section is auto-discovered by SectionRegistry on next request
```

### Create a New Block

```
- [ ] Create resources/views/blocks/{type}.blade.php
- [ ] Add @schema([...]) with name, settings, and blocks (if container)
- [ ] Add {!! $block->editorAttributes() !!} to the root HTML element
- [ ] Use $block->settings->key for setting values
- [ ] Use @blocks($block) if block is a container (e.g. row, accordion)
- [ ] Block is auto-discovered by BlockRegistry on next request
```

### Create a Page JSON Layout

```
- [ ] Create resources/views/pages/{slug}.json
- [ ] Define sections map keyed by unique IDs
- [ ] Set type to a registered section type
- [ ] Add settings, blocks, and order arrays
- [ ] Add top-level order array defining section render sequence
- [ ] Optionally add layout key for header/footer slot overrides
```

### Build a Theme

```
- [ ] Create themes/{name}/views/layouts/page.blade.php
- [ ] Add @pbEditorClass to <html>, @sections('header'), @yield('content'), @sections('footer')
- [ ] Create theme sections in themes/{name}/views/sections/
- [ ] Create theme blocks in themes/{name}/views/blocks/
- [ ] Register theme in config/themer.php
- [ ] Define theme_settings_schema in config/pagebuilder.php if needed
- [ ] Run php artisan theme:link to symlink public assets
```

### Theme directory structure

```
themes/
└── my-theme/
    ├── views/
    │   ├── layouts/
    │   │   └── page.blade.php       # Master layout
    │   ├── sections/               # Theme sections (shadows built-ins with same type)
    │   ├── blocks/                 # Theme-level reusable blocks
    │   └── pages/                  # Page JSON files
    └── assets/
        ├── css/
        └── js/
```

### Theme shadowing

Built-in sections/blocks are registered first; theme registrations happen after. **Last registration wins** — a theme's `row.blade.php` replaces the built-in one for all sections.

---

## Naming Conventions

| Item | Convention | Example |
|---|---|---|
| Section Blade file | `kebab-case.blade.php` | `site-header.blade.php` |
| Block Blade file | `kebab-case.blade.php` | `image-text.blade.php` |
| Setting `id` | `snake_case` | `background_color` |
| Page JSON file | `kebab-case.json` | `landing-page.json` |
| Layout slot key | `kebab-case` | `header`, `footer`, `top-bar` |
| Section / block type | Matches Blade filename | `site-header`, `image-text` |

---

## Best Practices

### Strict Typing

All PHP files must declare `declare(strict_types=1)`. All properties must be explicitly typed. All methods must have return types. Use `readonly` for value objects (Schema classes). Use PHP 8.2+ features.

### Dependency Injection

Always inject services via constructor — never call `new ClassName()` directly. All core services are singletons registered by `PageBuilderServiceProvider`.

### Schema Immutability

Schema objects (`SectionSchema`, `BlockSchema`, `SettingSchema`) must never be mutated after construction. They are pure value objects.

### Rendering Pipeline

All rendering must go through `Renderer`. Never call `view()->make()` or `Blade::render()` directly for sections or blocks. In Blade templates, always use `@blocks()`.

---

## Common Pitfalls

- **Missing `order` array** — every `blocks` map requires a matching `order` string[]; forgetting it breaks rendering order.
- **Wrong block reference type** — adding `name` to `['type' => 'row']` turns it into a local definition; the registry is no longer consulted.
- **Rendering directly from views** — never call `app(Renderer::class)->renderSection(...)` inside a Blade template.
- **Schema mutation** — never assign to a `SectionSchema` or `BlockSchema` property after construction.
- **Circular layer imports** — services may use `Renderer`; `Renderer` must not use any service.

---

## Key API Endpoints

| Purpose | Method | Endpoint |
|---|---|---|
| Load editor SPA | GET | `/pagebuilder/{slug?}` |
| List all pages | GET | `/pagebuilder/pages` |
| Get page JSON + layout | GET | `/pagebuilder/page/{slug}` |
| Render section (preview) | POST | `/pagebuilder/render-section` |
| Render block (preview) | POST | `/pagebuilder/render-block` |
| Save page JSON | POST | `/pagebuilder/save-page` |
| Get theme settings | GET | `/pagebuilder/theme-settings` |
| Save theme settings | POST | `/pagebuilder/theme-settings` |
| List assets | GET | `/pagebuilder/assets` |
| Upload asset | POST | `/pagebuilder/assets/upload` |
| Render published page | GET | `/{slug}` |
