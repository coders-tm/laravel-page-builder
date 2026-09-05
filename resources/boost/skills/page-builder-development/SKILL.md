---
name: page-builder-development
description: Laravel Page Builder development. Activate when creating or modifying sections, blocks, themes, page JSON layouts, schema definitions, registry classes, rendering pipeline, editor integration, or any feature of the coderstm/laravel-page-builder package.
---

# Laravel Page Builder Development

A multi-theme, JSON-driven page composition system for Laravel. Pages are assembled from **sections** (primary structural units) that contain **blocks** (reusable content elements), all driven by a Page JSON document. The editor reads schemas defined inline in Blade files via `@schema()` — no separate schema files needed.

## Architecture

Five layers — dependencies flow **downward only**. Lower layers never import from higher layers.

```
Schema → Registry → Components (Runtime) → Rendering → Services/Controllers
```

### Layer 1 — Schema (`src/Schema/`)

Immutable `readonly` value objects that describe what a section or block _can_ look like.

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

Discovers, stores, and provides typed schema objects.

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

Runtime instances hydrated from page JSON using schema defaults.

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

Converts runtime objects into HTML via Blade views.

| Class              | Key Methods                                                                                             |
| ------------------ | ------------------------------------------------------------------------------------------------------- |
| `Renderer`         | `renderSection`, `renderBlock`, `renderBlocks`, `renderBlockChildren`, `hydrateSection`, `hydrateBlock` |
| `EditorAttributes` | `forSection`, `forBlock`, `autoInjectLiveText`                                                          |
| `BladeDirectives`  | Registers `@blocks`, `@schema`, `@sections`, `@editor`                                                  |

Rules:

- ALL rendering goes through `Renderer` — never render sections or blocks directly from views.
- In editor mode, `autoInjectLiveText` injects `data-live-text-setting` on string settings automatically.
- `@blocks($section)` → renders all top-level blocks; `@blocks($block)` → renders nested child blocks.

### Layer 5 — Services (`src/Services/`)

High-level orchestrators for page loading, rendering, and persistence.

| Class                      | Responsibility                                                                                     |
| -------------------------- | -------------------------------------------------------------------------------------------------- |
| `PageRenderer`             | Loads page JSON → hydrates all sections → renders complete HTML; applies wrapper                   |
| `PageStorage`              | JSON file I/O for page data; handles layout splitting and **locale-aware** file resolution         |
| `TemplateStorage`          | JSON file I/O for template data (theme-aware, **locale-aware**, `config('pagebuilder.templates')`) |
| `TemplateVariableResolver` | Resolves `{{ $page->attr }}` placeholders in template section settings                             |
| `WrapperParser`            | Parses CSS-selector wrapper strings (e.g. `div#id.class`) into HTML elements                       |
| `PageRegistry`             | Cached page manifest (`bootstrap/cache/pagebuilder_pages.php`)                                     |
| `PageService`              | Route registration + page resolution (Blade → JSON → template → 404)                               |
| `SettingsStore`            | Low-level `settings.json` file I/O, caching, and key preservation service                          |
| `ThemeSettings`            | Type-safe theme settings management via `SettingsStoreInterface`                                   |
| `LayoutSettings`           | Type-safe shared layout config management via `SettingsStoreInterface`                             |
| `Theme`                    | Active theme management wrapper                                                                    |

### Key Classes

| Class                      | Path                                        | Purpose                                                            |
| -------------------------- | ------------------------------------------- | ------------------------------------------------------------------ |
| `SectionSchema`            | `src/Schema/SectionSchema.php`              | Immutable section definition                                       |
| `BlockSchema`              | `src/Schema/BlockSchema.php`                | Immutable block definition                                         |
| `SettingSchema`            | `src/Schema/SettingSchema.php`              | Immutable setting definition                                       |
| `SectionRegistry`          | `src/Registry/SectionRegistry.php`          | Discovers and provides section schemas                             |
| `BlockRegistry`            | `src/Registry/BlockRegistry.php`            | Discovers and provides block schemas                               |
| `SchemaExtractor`          | `src/Registry/SchemaExtractor.php`          | Parses `@schema()` from Blade files                                |
| `Renderer`                 | `src/Rendering/Renderer.php`                | Core hydration and rendering engine                                |
| `Section`                  | `src/Components/Section.php`                | Runtime section instance                                           |
| `Block`                    | `src/Components/Block.php`                  | Runtime block instance                                             |
| `Settings`                 | `src/Components/Settings.php`               | Schema-aware settings bag                                          |
| `PageData`                 | `src/Support/PageData.php`                  | Type-safe page JSON value object DTO (with `PageMeta` & `wrapper`) |
| `PageMeta`                 | `src/Support/PageMeta.php`                  | Immutable SEO metadata Value Object                                |
| `LayoutConfig`             | `src/Support/LayoutConfig.php`              | Type-safe layout zone Value Object DTO                             |
| `SettingsStore`            | `src/Services/SettingsStore.php`            | Atomic settings JSON file persistence                              |
| `SettingsStoreInterface`   | `src/Contracts/SettingsStoreInterface.php`  | Storage contract for settings.json                                 |
| `PageStorage`              | `src/Services/PageStorage.php`              | Page JSON file I/O + layout splitting                              |
| `PageRenderer`             | `src/Services/PageRenderer.php`             | Full-page render orchestrator (applies wrapper)                    |
| `TemplateStorage`          | `src/Services/TemplateStorage.php`          | Template JSON file I/O (theme-aware)                               |
| `TemplateVariableResolver` | `src/Support/TemplateVariableResolver.php`  | Resolves `{{ $page->attr }}` in template data                      |
| `WrapperParser`            | `src/Support/WrapperParser.php`             | Parses CSS-selector wrapper strings into HTML                      |
| `ThemeSettings`            | `src/Services/ThemeSettings.php`            | Type-safe theme settings service                                   |
| `LayoutSettings`           | `src/Services/LayoutSettings.php`           | Type-safe layout settings service                                  |
| `SetLangMiddleware`        | `src/Http/Middleware/SetLangMiddleware.php` | Sets page builder language from route param or query string        |

---

## Configuration

`config/pagebuilder.php`:

```php
return [
    'pages'                 => resource_path('views/pages'),          // Page JSON files dir
    'sections'              => resource_path('views/sections'),        // Section Blade dir
    'blocks'                => resource_path('views/blocks'),          // Block Blade dir
    'templates'             => resource_path('views/templates'),       // Template JSON files dir
    'middleware'             => ['web'],                               // Route middleware
    'disk'                  => 'public',                               // Storage disk for assets
    'asset_directory'       => 'pagebuilder',                          // Sub-dir on disk
    'languages'             => [],                                     // Available languages (empty = disabled)
    'theme_settings_schema' => [],                                     // Global theme settings
    'theme_settings_path'   => resource_path('settings.json'),         // Theme settings file
];
```

---

## Page Model

```php
// src/Models/Page.php
$fillable = ['parent', 'title', 'slug', 'meta_title', 'meta_keywords',
             'meta_description', 'is_active', 'template', 'metadata', 'content'];
$casts    = ['is_active' => 'boolean', 'metadata' => 'json'];
```

---

## Facades

```php
use PageBuilder\Facades\Section;   // → SectionRegistry
use PageBuilder\Facades\Block;     // → BlockRegistry
use PageBuilder\Facades\Page;      // → PageService
use PageBuilder\Facades\Theme;     // → Theme service

Section::get('hero');       // → SectionSchema
Block::get('row');          // → BlockSchema
Page::findBySlug('home');   // → Page model
Theme::active();            // → active theme name
```

### Language API

```php
use PageBuilder\PageBuilder;

PageBuilder::setLang('fr');  // Set language for locale-aware file resolution
PageBuilder::getLang();      // Returns 'fr' or null (default language)
```

When a language is set, `PageStorage` and `TemplateStorage` resolve locale-specific files first (e.g., `home.fr.json`) before falling back to the default (`home.json`).

---

## Helper Functions

```php
pb_editor();    // bool — true when editor mode is active
theme();        // string — returns active theme name
theme_vite();   // Vite — returns Vite asset loader for the active theme
```

---

## Artisan Commands

```bash
php artisan pages:regenerate   # Regenerate the page registry cache
php artisan theme:link         # Symlink theme public assets
```

---

## Blade Directives

| Directive             | Purpose                                                                     |
| --------------------- | --------------------------------------------------------------------------- |
| `@schema([...])`      | Schema definition; no-op at render time, extracted at registration          |
| `@blocks($section)`   | Renders all top-level blocks of a section                                   |
| `@blocks($block)`     | Renders all child blocks of a container block                               |
| `@sections('header')` | Renders a layout slot (header / footer)                                     |
| `@layout([...])`      | Partial layout config override for custom Blade pages                       |
| `@editor('dark')`     | Renders the `<html>` class attribute and adds editor classes in editor mode |

> Never call `@blocks()` outside a Blade view. Never call the renderer directly from a template.

---

## Sections

A section is the **primary structural unit** of a page. It is a Blade view file identified by its filename (without `.blade.php`), auto-discovered by `SectionRegistry`.

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

### SectionSchema PHP Class

```php
use PageBuilder\Schema\SectionSchema;

$schema = SectionSchema::fromArray([
    'name'     => 'Hero',
    'settings' => [...],
    'blocks'   => [...],
]);

$schema->name;                 // 'Hero'
$schema->settings;             // SettingSchema[]
$schema->blocks;               // BlockSchema[] (local definitions)
$schema->allowedBlockTypes;    // string[] (bare type references)
$schema->acceptsThemeBlocks(); // bool — true if '@theme' is in blocks
$schema->settingDefaults();    // ['title' => 'Welcome', ...]
$schema->blockSchema('row');   // BlockSchema|null for local definition
```

### SectionRegistry

```php
use PageBuilder\Facades\Section;

$schema = Section::get('hero');        // SectionSchema — get by type
$all = Section::all();                 // SectionSchema[] — get all
$exists = Section::has('hero');        // bool — check if registered
```

### Built-in section (`section.blade.php`)

The package ships with one built-in section at `resources/views/sections/section.blade.php`:

| Setting                      | Type         | Default   | Notes                             |
| ---------------------------- | ------------ | --------- | --------------------------------- |
| `anchor`                     | text         | `''`      | HTML `id` attribute               |
| `padding_top`                | select       | `md`      | none/xs/sm/md/lg/xl/2xl           |
| `padding_bottom`             | select       | `md`      | none/xs/sm/md/lg/xl/2xl           |
| `max_width`                  | select       | `7xl`     | full/sm/md/lg/xl/2xl/5xl/6xl/7xl  |
| `background_color`           | color        | `''`      | CSS background color              |
| `background_image`           | image_picker | `''`      | Background image URL              |
| `background_overlay_opacity` | range        | `0`       | 0–100                             |
| `color_scheme`               | select       | `default` | default/light/dark/primary/accent |

Accepted blocks: `row`, `@theme`

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

### BlockSchema PHP Class

```php
use PageBuilder\Schema\BlockSchema;

$schema = BlockSchema::fromArray([
    'name'     => 'Row',
    'settings' => [...],
    'blocks'   => [...],
]);

$schema->type;                 // auto-set from Blade filename
$schema->name;                 // 'Row'
$schema->settings;             // SettingSchema[]
$schema->blocks;               // BlockSchema[] (local child definitions)
$schema->allowedBlockTypes;    // string[] (bare type references)
$schema->acceptsThemeBlocks(); // bool
$schema->settingDefaults();    // ['columns' => '2', 'gap' => 'md', ...]
```

### BlockRegistry

```php
use PageBuilder\Facades\Block;

$schema = Block::get('row');       // BlockSchema — get by type
$all = Block::all();               // BlockSchema[] — get all
$exists = Block::has('row');       // bool — check if registered
```

### Local vs theme block references

| Entry in `blocks` array                            | Detected as                | Resolved via                   |
| -------------------------------------------------- | -------------------------- | ------------------------------ |
| `['type' => 'row']` — only `type` key              | Theme reference            | `BlockRegistry::get('row')`    |
| `['type' => '@theme']`                             | Wildcard — any theme block | `BlockRegistry::all()`         |
| `['type' => 'item', 'name' => '...']` — extra keys | Local definition           | Used as-is; no registry lookup |

> Never add `name` or `settings` to a bare theme reference — it changes the lookup behaviour.

### Built-in theme blocks

#### `row.blade.php` — Responsive Grid Row

| Setting              | Type     | Default | Notes                           |
| -------------------- | -------- | ------- | ------------------------------- |
| `columns`            | select   | `2`     | 1–6 columns                     |
| `gap`                | select   | `md`    | none/xs/sm/md/lg/xl             |
| `vertical_alignment` | select   | `start` | start/center/end/stretch        |
| `reverse_on_mobile`  | checkbox | `false` | Reverses column order on mobile |
| `full_width`         | checkbox | `false` | Future full-bleed override      |

Accepted child blocks: `column`

#### `column.blade.php` — Flex Column

| Setting                | Type         | Default | Notes                            |
| ---------------------- | ------------ | ------- | -------------------------------- |
| `width`                | select       | `auto`  | auto or col-span-1 to col-span-6 |
| `horizontal_alignment` | select       | `start` | start/center/end                 |
| `vertical_alignment`   | select       | `start` | start/center/end/between         |
| `padding`              | select       | `none`  | none/sm/md/lg/xl                 |
| `background_color`     | color        | `''`    | CSS background color             |
| `background_image`     | image_picker | `''`    | Background image                 |
| `hide_on_mobile`       | checkbox     | `false` | Hidden below `sm`                |
| `hide_on_desktop`      | checkbox     | `false` | Hidden above `sm`                |

Accepted child blocks: `@theme` (any)

---

## Setting Types

| Type           | Description             | Extra fields                |
| -------------- | ----------------------- | --------------------------- |
| `text`         | Single-line text input  | —                           |
| `textarea`     | Multi-line text         | —                           |
| `richtext`     | TipTap rich-text editor | —                           |
| `number`       | Numeric input           | `min`, `max`, `step`        |
| `range`        | Slider                  | `min`, `max`, `step`        |
| `select`       | Dropdown                | `options: [{value, label}]` |
| `radio`        | Radio group             | `options: [{value, label}]` |
| `checkbox`     | Boolean toggle          | —                           |
| `color`        | Color picker            | —                           |
| `image_picker` | Media library image     | —                           |
| `alignment`    | Alignment picker        | —                           |
| `icon_picker`  | Icon selector           | —                           |
| `url`          | URL input               | —                           |

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

When a language is set, locale-specific files are resolved first (e.g., `{slug}.fr.json`) before falling back to the default.

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
            "col-left": { "type": "column", "settings": {}, "blocks": {}, "order": [] },
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

#### Top-level fields

| Field      | Type             | Required | Description                                        |
| ---------- | ---------------- | -------- | -------------------------------------------------- |
| `sections` | object           | yes      | Map of section instances keyed by unique ID        |
| `order`    | string[]         | yes      | Render order of section IDs                        |
| `layout`   | string \| object | no       | Layout type (string) or per-page override (object) |

#### Section instance fields

| Field      | Type     | Required | Description                                                    |
| ---------- | -------- | -------- | -------------------------------------------------------------- |
| `type`     | string   | yes      | Matches a registered `SectionSchema` (Blade filename)          |
| `settings` | object   | yes      | Key→value overrides; missing keys fall back to schema defaults |
| `blocks`   | object   | yes      | Map of block instances keyed by unique ID                      |
| `order`    | string[] | yes      | Render order of block IDs within this section                  |
| `disabled` | boolean  | no       | `true` hides section from rendered output                      |
| `_name`    | string   | no       | Custom display label in editor                                 |

#### Block instance fields

| Field      | Type     | Required | Description                                                |
| ---------- | -------- | -------- | ---------------------------------------------------------- |
| `type`     | string   | yes      | Matches a registered `BlockSchema` key or local definition |
| `settings` | object   | yes      | Key→value overrides                                        |
| `blocks`   | object   | yes      | Nested child block instances (for container blocks)        |
| `order`    | string[] | yes      | Render order of child block IDs                            |
| `disabled` | boolean  | no       | `true` excludes block from rendered output                 |

> Every `blocks` map requires a matching `order` array. Missing `order` is a common bug.

### Layout sections (Header / Footer)

The `layout` key defines structural slots rendered **outside** the main `@yield` area. It supports two formats:

#### String format (shared layout)

```json
{
  "layout": "page"
}
```

When `layout` is a string, the page **inherits the shared layout** from `LayoutSettings` (stored in `_pagebuilder.layouts` in `settings.json`).

#### Object format (page-specific override)

```json
{
  "layout": {
    "type": "page",
    "header": {
      "sections": {
        "header": {
          "type": "site-header",
          "settings": { "sticky": true },
          "blocks": {},
          "order": []
        }
      }
    },
    "footer": {
      "sections": {
        "footer": {
          "type": "site-footer",
          "settings": {},
          "blocks": {},
          "order": []
        }
      }
    }
  }
}
```

When `layout` is an object, the page has a **page-specific override** stored in the page JSON.

#### Layout section rules

- `layout.header.sections` and `layout.footer.sections` are keyed by **position slug** (`"header"`, `"footer"`, etc.), not a random ID.
- Keys that match `"header"` or carry `position: "top"` render in the **top zone**.
- All other keys fall to the **bottom zone**.
- `disabled: true` causes `@sections('key')` to return an empty string.
- `_name` is supported to override the schema display name in the editor.
- Layout sections are **not sortable** — their position is determined by the Blade layout.

#### How layout saving works

When saving, `PageStorage` checks the **existing** page.json's layout:

| Existing layout   | Save behavior                                   |
| ----------------- | ----------------------------------------------- |
| Not exists        | Save to `LayoutSettings`, store `"page"` string |
| String (`"page"`) | Save to `LayoutSettings`, store string          |
| Object (`{...}`)  | Save to page.json, strip `source` key           |

### Settings resolution priority

1. Value stored in page JSON for this instance
2. Default from `SettingSchema` (the `default` key in `@schema`)
3. `null` if no default exists

### Nesting depth

Blocks can nest to any depth. The `Renderer` hydrates them recursively via `hydrateBlocks()`:

```
Section
  └── Row (block)
        ├── Column (block)
        │     └── Card (block)
        └── Column (block)
```

Each level uses `blocks` (map) + `order` (array) for deterministic render order.

### Disabled filtering

Disabled blocks are **silently excluded** during hydration — they never appear in `BlockCollection` and never render. Disabled sections are excluded by `SectionCollection::enabled()` before rendering.

### PageData PHP Access

```php
use PageBuilder\Support\PageData;

$pageData = PageData::fromArray($rawJson);

$pageData->sections;          // array of raw section data
$pageData->order;             // string[] render order
$pageData->layout;            // ?array layout zones
$pageData->toArray();         // back to raw array for storage
```

### Creating / Saving a Page

```php
use PageBuilder\Services\PageStorage;

$storage = app(PageStorage::class);

$json = $storage->load('home');    // returns PageData
$storage->save('home', $pageData->toArray());
```

---

## Templates

Templates are **JSON fallback layouts** for pages that have no per-page JSON file (`pages/{slug}.json`) and no custom Blade view (`pages/{slug}.blade.php`). A single JSON file defines the sections, render order, optional wrapper element, and layout type for any page that uses it.

When a language is set, locale-specific templates are resolved first (e.g., `page.fr.json`) before falling back to the default.

A page selects its template via the `template` column on the `Page` model. When no template is selected, the default `page.json` template is used.

### Page rendering resolution order

```
1. Editor mode              → always renders from page JSON (bypasses all below)
2. pages/{slug}.{lang}.blade.php → locale-specific custom Blade view (when lang set)
3. pages/{slug}.blade.php   → custom Blade view wins if it exists
4. pages/{slug}.{lang}.json → locale-specific page JSON (when lang set)
5. pages/{slug}.json        → stored page builder JSON wins if it exists
6. templates/{name}.{lang}.json → locale-specific template (when lang set)
7. templates/{name}.json    → template selected by Page::$template, or page.json default
8. 404
```

Templates are **only consulted** when steps 2 and 3 both miss. A template never overrides an existing page JSON.

### Template JSON schema

```json
{
  "layout": "page",
  "wrapper": "main#content.container",
  "sections": {
    "main": {
      "type": "page-content",
      "settings": {}
    }
  },
  "order": ["main"]
}
```

#### Root fields

| Field      | Type            | Required | Description                                                                         |
| ---------- | --------------- | -------- | ----------------------------------------------------------------------------------- |
| `sections` | object          | yes      | Section data map — same format as page JSON sections                                |
| `order`    | string[]        | yes      | Section render order — IDs must exist in `sections`                                 |
| `layout`   | string \| false | no       | Layout type for `LayoutParser`. Defaults to `"page"`. `false` disables layout zones |
| `wrapper`  | string          | no       | CSS-selector string to wrap rendered sections in an HTML element                    |

#### Template vs Page JSON differences

| Aspect            | Page JSON                                 | Template JSON                                 |
| ----------------- | ----------------------------------------- | --------------------------------------------- |
| `layout`          | Complex object `{type, header, footer}`   | Simple string (type name) or `false`          |
| `wrapper`         | Not supported                             | Optional HTML wrapper around all sections     |
| Storage location  | `config('pagebuilder.pages')/{slug}.json` | `config('pagebuilder.templates')/{name}.json` |
| Mutated by editor | Yes                                       | No — templates are read-only at runtime       |

### The `wrapper` property

The `wrapper` field accepts a CSS-selector-like string and wraps all rendered section HTML in a single element.

**Syntax:**

```
tag#id.class1.class2[attr1=val1][attr2=val2]
```

- **Tag** — `div`, `main`, or `section` (defaults to `div` for any other value)
- **`#id`** — sets the element `id` attribute
- **`.class`** — sets the element `class` attribute (multiple classes joined with spaces)
- **`[key=value]`** — sets arbitrary HTML attributes

**Example:**

```json
{ "wrapper": "div#div_id.div_class[attribute-one=value]" }
```

Output:

```html
<div id="div_id" class="div_class" attribute-one="value">
  <!-- rendered sections -->
</div>
```

Only `div`, `main`, `section` are accepted as wrapper tags. Any other tag falls back to `<div>`.

### Variable interpolation

Template section settings can embed `{{ $page->attribute }}` placeholders. At render time, `TemplateVariableResolver` replaces these with the corresponding attribute from the `Page` Eloquent model.

```json
{
  "sections": {
    "hero": {
      "type": "hero",
      "settings": {
        "title": "{{ $page->title }}",
        "description": "{{ $page->meta_description }}"
      }
    }
  }
}
```

Rules:

- Whitespace around the expression is ignored: `{{$page->title}}` and `{{ $page->title }}` are both valid.
- Only `$page->attribute` access is supported — no method calls or expressions.
- If the attribute is `null` or does not exist, the placeholder resolves to an empty string.
- When there is no DB page (guest page without a model), all placeholders resolve to `""`.
- Non-page placeholders (e.g. `{{ $other->title }}`) are left unchanged.

### Available page attributes

| Attribute          | Type         | Notes                          |
| ------------------ | ------------ | ------------------------------ |
| `title`            | string       | Page title                     |
| `slug`             | string       | URL slug                       |
| `content`          | string       | Page body HTML                 |
| `meta_title`       | string\|null | SEO title                      |
| `meta_description` | string\|null | SEO description                |
| `meta_keywords`    | string\|null | SEO keywords                   |
| `template`         | string\|null | Template name                  |
| Any custom column  | mixed        | Cast to string on substitution |

### Theme-aware template resolution

`TemplateStorage::load()` checks the active theme path before falling back to the configured templates directory. When a language is set, locale-specific templates are checked first:

```
1. Theme::path('views/templates/{name}.{lang}.json')   → active theme locale
2. Theme::path('views/templates/{name}.json')           → active theme default
3. config('pagebuilder.templates')/{name}.{lang}.json   → app templates locale
4. config('pagebuilder.templates')/{name}.json          → app templates default
```

A theme can override the default `page.json` template by providing `views/templates/page.json` inside the theme directory.

### Template naming

Files live in `config('pagebuilder.templates')` (default: `resources/views/templates/`).

| Filename              | Template name           | DB `template` field value |
| --------------------- | ----------------------- | ------------------------- |
| `page.json`           | Default page template   | `null` / `""` / `"page"`  |
| `page.alternate.json` | Alternate page template | `"page.alternate"`        |
| `product.json`        | Product template        | `"product"`               |

Rules:

- The `.json` extension is always stripped when looking up by name.
- Template names are normalised to lowercase before lookup.
- If a requested template file does not exist, `TemplateStorage` returns `null` and `PageService` falls back to `page.json`. If `page.json` also does not exist, the request returns 404.

### Data flow

```
Request /about
  ↓ PageService::render('about')
  ↓ No pages/about.blade.php, No pages/about.json
  ↓ PageService::resolveTemplate($dbPage)
        ↓ TemplateStorage::load($dbPage->template ?? 'page')
        ↓ Returns raw array or null (falls back to page.json)
  ↓ TemplateVariableResolver::resolve($rawData, $dbPage)
        ↓ Replaces {{ $page->title }} with actual values
  ↓ PageService::resolveTemplateLayout($resolvedData)
        ↓ layout: "page"  → LayoutParser::defaultLayout('page')
        ↓ layout: false   → []  (no header/footer zones)
  ↓ PageService::buildPageFromTemplate($resolvedData, $defaultLayout, $dbPage)
        ↓ PageData::fromArray([sections, order, wrapper, title], $defaultLayout)
  ↓ PageRenderer::renderPage($pageData)
        ↓ Renders each section via Renderer
        ↓ If wrapper set → WrapperParser::render($wrapper, $sectionsHtml)
  ↓ view('pagebuilder::page', [...])
```

---

## Multilanguage

The page builder supports multilanguage page resolution. When a language is set, the system first looks for locale-specific files before falling back to the default.

### File resolution order

| Layer         | Default                  | Locale-specific (e.g. `fr`)                                     |
| ------------- | ------------------------ | --------------------------------------------------------------- |
| Page JSON     | `pages/{slug}.json`      | `pages/{slug}.fr.json` → fallback `pages/{slug}.json`           |
| Custom Blade  | `pages/{slug}.blade.php` | `pages/{slug}.fr.blade.php` → fallback `pages/{slug}.blade.php` |
| Template JSON | `templates/{name}.json`  | `templates/{name}.fr.json` → fallback `templates/{name}.json`   |

### Configuration

```php
// config/pagebuilder.php
'languages' => [
    ['code' => 'en', 'name' => 'English'],   // first entry = default
    ['code' => 'fr', 'name' => 'Français'],
    ['code' => 'es', 'name' => 'Español'],
],
```

When `languages` is empty, multilanguage is disabled and no language selector appears in the editor.

### Setting the language

```php
// From middleware
PageBuilder::setLang('fr');

// From route parameter
Route::get('/{slug}', ...)->middleware('lang:fr');

// From query string (via SetLangMiddleware)
// GET /about?lang=fr
```

### Editor integration

When languages are configured, the editor header shows a Globe icon. Clicking it opens a popover with available languages. Selecting a language:

1. Updates the URL search params (`?lang=fr`)
2. Reloads the current page with the new language
3. Saves to locale-specific JSON files (`{slug}.{lang}.json`)

### Middleware

The `lang` middleware alias is registered automatically:

```php
Route::middleware(['lang:fr'])->group(function () {
    // All pages in this group resolve French locale files
});
```

---

## Themes

A theme is a collection of **sections**, **blocks**, **layouts**, and **assets** that define the visual and structural identity of a site. Themes follow `qirolab/laravel-themer` conventions and are discovered automatically by the Page Builder.

### Theme directory structure

```
themes/
└── my-theme/
    ├── views/
    │   ├── layouts/
    │   │   └── page.blade.php         # Master layout (header, main, footer)
    │   ├── sections/                  # Theme sections (override or extend built-ins)
    │   │   ├── hero.blade.php
    │   │   ├── features.blade.php
    │   │   ├── cta.blade.php
    │   │   └── site-header.blade.php
    │   ├── blocks/                    # Theme-level reusable blocks
    │   │   ├── row.blade.php          # Can override built-in row
    │   │   ├── column.blade.php       # Can override built-in column
    │   │   ├── card.blade.php
    │   │   └── button.blade.php
    │   └── pages/                     # Page JSON files
    │       ├── home.json
    │       └── about.json
    └── assets/
        ├── css/
        │   └── theme.css
        └── js/
            └── theme.js
```

### Theme registration

Themes are registered in `config/themer.php` (from `qirolab/laravel-themer`):

```php
return [
    'active_theme' => env('APP_THEME', 'default'),
    'themes_path'  => resource_path('themes'),
];
```

The Page Builder reads the active theme from this config and registers its sections and blocks on top of the built-in ones.

**Last registration wins** — theme sections/blocks shadow built-in ones with the same filename/type.

### Master layout (`layouts/page.blade.php`)

```blade
{{-- themes/my-theme/views/layouts/page.blade.php --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @editor>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->meta_title ?? $page->title ?? config('app.name') }}</title>

    {{-- Theme assets --}}
    {{ theme_vite(['css/theme.css', 'js/theme.js']) }}
</head>
<body>

    {{-- Layout section: header --}}
    @sections('header')

    {{-- Page sections rendered here --}}
    @yield('content')

    {{-- Layout section: footer --}}
    @sections('footer')

</body>
</html>
```

Key directives:

| Directive             | Purpose                                                   |
| --------------------- | --------------------------------------------------------- |
| `@editor`             | Adds `js pb-design-mode` class to `<html>` in editor mode |
| `@sections('header')` | Renders the `header` layout section                       |
| `@sections('footer')` | Renders the `footer` layout section                       |
| `@yield('content')`   | Where the page's sections are output                      |

### Theme sections

Theme sections live at `themes/{name}/views/sections/` and follow the same rules as sections.

```blade
{{-- themes/my-theme/views/sections/cta.blade.php --}}

@schema([
    'name' => 'Call to Action',
    'settings' => [
        ['id' => 'title',       'type' => 'text',    'label' => 'Title',       'default' => 'Ready to Get Started?'],
        ['id' => 'description', 'type' => 'textarea', 'label' => 'Description', 'default' => ''],
        ['id' => 'button_text', 'type' => 'text',    'label' => 'Button Text', 'default' => 'Get Started'],
        ['id' => 'button_url',  'type' => 'url',     'label' => 'Button URL',  'default' => '#'],
        ['id' => 'bg_color',    'type' => 'color',   'label' => 'Background',  'default' => '#1d4ed8'],
        ['id' => 'text_color',  'type' => 'color',   'label' => 'Text Color',  'default' => '#ffffff'],
    ],
    'presets' => [
        ['name' => 'CTA Banner'],
    ],
])

<section {!! $section->editorAttributes() !!}
    style="background-color: {{ $section->settings->bg_color }}; color: {{ $section->settings->text_color }}">
    <div class="container mx-auto text-center py-20">
        <h2 class="text-4xl font-bold mb-4">{{ $section->settings->title }}</h2>
        @if($section->settings->description)
            <p class="text-lg mb-8">{{ $section->settings->description }}</p>
        @endif
        <a href="{{ $section->settings->button_url }}"
           class="inline-block bg-white font-semibold py-3 px-8 rounded-lg"
           style="color: {{ $section->settings->bg_color }}">
            {{ $section->settings->button_text }}
        </a>
    </div>
</section>
```

### Theme blocks

Theme blocks live at `themes/{name}/views/blocks/` and are auto-registered as **theme blocks** (reusable across any section that accepts `['type' => '@theme']` or a bare reference).

```blade
{{-- themes/my-theme/views/blocks/image-text.blade.php --}}

@schema([
    'name' => 'Image + Text',
    'settings' => [
        ['id' => 'image',      'type' => 'image_picker', 'label' => 'Image',      'default' => ''],
        ['id' => 'heading',    'type' => 'text',         'label' => 'Heading',    'default' => ''],
        ['id' => 'body',       'type' => 'richtext',     'label' => 'Body',       'default' => ''],
        ['id' => 'image_side', 'type' => 'select',       'label' => 'Image Side', 'default' => 'left',
         'options' => [
             ['value' => 'left',  'label' => 'Left'],
             ['value' => 'right', 'label' => 'Right'],
         ]],
    ],
])

<div {!! $block->editorAttributes() !!}
     class="flex {{ $block->settings->image_side === 'right' ? 'flex-row-reverse' : 'flex-row' }} gap-12 items-center">
    @if($block->settings->image)
        <div class="flex-1">
            <img src="{{ $block->settings->image }}" alt="{{ $block->settings->heading }}" class="w-full rounded-lg">
        </div>
    @endif
    <div class="flex-1">
        <h3 class="text-2xl font-bold mb-4">{{ $block->settings->heading }}</h3>
        <div class="prose">{!! $block->settings->body !!}</div>
    </div>
</div>
```

### Theme settings (global)

Theme settings are global settings that apply across all pages. They are configured in `config/pagebuilder.php`:

```php
'theme_settings_schema' => [
    [
        'id'      => 'colors.primary',
        'type'    => 'color',
        'label'   => 'Primary Color',
        'default' => '#1d4ed8',
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
'theme_settings_path' => resource_path('settings.json'),
```

Accessing theme settings in Blade:

```blade
<body style="--primary: {{ $theme->get('colors.primary', '#1d4ed8') }}">
```

### Asset loading

**Vite (recommended):**

```php
theme_vite(['css/theme.css', 'js/theme.js'])
```

**Mix (legacy):**

```php
theme_mix('css/theme.css')
```

### Theme shadowing rules

When the same section/block type is registered by both the built-in package AND a theme:

1. **Built-in** is registered first (during `ServiceProvider::boot()`).
2. **Theme** is registered second.
3. **Last registration wins** — the theme version is used.

This allows themes to completely replace `row.blade.php`, `column.blade.php`, or any built-in section with a custom implementation.

---

## Naming Conventions

| Item                 | Convention             | Example                       |
| -------------------- | ---------------------- | ----------------------------- |
| Section Blade file   | `kebab-case.blade.php` | `site-header.blade.php`       |
| Block Blade file     | `kebab-case.blade.php` | `image-text.blade.php`        |
| Setting `id`         | `snake_case`           | `background_color`            |
| Page JSON file       | `kebab-case.json`      | `landing-page.json`           |
| Layout slot key      | `kebab-case`           | `header`, `footer`, `top-bar` |
| Section / block type | Matches Blade filename | `site-header`, `image-text`   |

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
- [ ] Add @editor(...) to <html>, @sections('header'), @yield('content'), @sections('footer')
- [ ] Create theme sections in themes/{name}/views/sections/
- [ ] Create theme blocks in themes/{name}/views/blocks/
- [ ] Register theme in config/themer.php
- [ ] Define theme_settings_schema in config/pagebuilder.php if needed
- [ ] Run php artisan theme:link to symlink public assets
```

### Create a Template

```
- [ ] Create resources/views/templates/{name}.json
- [ ] Declare sections, order, optional layout (string or false), optional wrapper
- [ ] Use {{ $page->title }} in settings values to interpolate page model attributes
- [ ] Assign the template to a page via Page::$template = 'name'
```

---

## Best Practices

### Strict Typing

All PHP files must declare `declare(strict_types=1)`. All properties must be explicitly typed. All methods must have return types. Use `readonly` for value objects (Schema classes). Use PHP 8.2+ features.

```php
declare(strict_types=1);

namespace PageBuilder\Schema;

final class SectionSchema
{
    public readonly string $name;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? '';
    }
}
```

### Dependency Injection

Always inject via constructor — never instantiate directly. All core services are singletons registered by `PageBuilderServiceProvider`.

```php
// WRONG
$renderer = new Renderer();

// CORRECT
public function __construct(
    private readonly Renderer $renderer,
) {}
```

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
- **Template ignored when JSON exists** — if `pages/{slug}.json` exists for a page, the template is never consulted.
- **`layout: false` skips header/footer** — when set, `@sections('header')` and `@sections('footer')` render nothing.
- **`@layout` must be placed after a blank line following `@extends`** — Blade's compiler requires `@extends` to be the first statement in the view. Placing `@layout` immediately after `@extends` without a blank line causes `@extends` to be silently dropped, resulting in an empty rendered output. Always add a blank line between `@extends` and `@layout`.
- **Wrapper tag not in allowed list** — only `div`, `main`, `section` are accepted; other tags fall back to `<div>`.
- **Circular variable reference** — `{{ $page->template }}` resolves to the template name string, not rendered content.
- **Non-string placeholder values** — numbers and booleans on the model are cast to string (e.g. `true` → `"1"`).

---

## Key API Endpoints

| Purpose                  | Method | Endpoint                      |
| ------------------------ | ------ | ----------------------------- |
| Load editor SPA          | GET    | `/pagebuilder/{slug?}`        |
| List all pages           | GET    | `/pagebuilder/pages`          |
| Get page JSON + layout   | GET    | `/pagebuilder/page/{slug}`    |
| Render section (preview) | POST   | `/pagebuilder/render-section` |
| Render block (preview)   | POST   | `/pagebuilder/render-block`   |
| Save page JSON           | POST   | `/pagebuilder/save-page`      |
| Get theme settings       | GET    | `/pagebuilder/theme-settings` |
| Save theme settings      | POST   | `/pagebuilder/theme-settings` |
| List assets              | GET    | `/pagebuilder/assets`         |
| Upload asset             | POST   | `/pagebuilder/assets/upload`  |
| Render published page    | GET    | `/{slug}`                     |
