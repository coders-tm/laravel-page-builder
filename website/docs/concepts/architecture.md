---
title: Architecture
---

# Architecture

Laravel Page Builder follows a five-layer architecture. Dependencies flow **downward only**. Never import from a higher layer.

```
Schema → Registry → Components → Rendering → Services/Controllers
```

## The Five Layers

### 1. Schema

Immutable value objects that define the structure of sections, blocks, and settings.

- `SectionSchema` — Defines a section type with its settings and allowed blocks
- `BlockSchema` — Defines a block type with its settings
- `SettingSchema` — Defines a single setting with type, label, and default value

```php
use PageBuilder\Schema\SectionSchema;

$schema = new SectionSchema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome'],
    ],
]);
```

### 2. Registry

Discovers and stores schemas from Blade `@schema()` directives.

- `SectionRegistry` — Discovers section Blade files and extracts schemas
- `BlockRegistry` — Discovers block Blade files and extracts schemas
- `SchemaExtractor` — Parses `@schema` directives from Blade files

```php
use PageBuilder\Facades\Section;
use PageBuilder\Facades\Block;

// Register additional paths
Section::add(resource_path('views/custom-sections'));
Block::add(resource_path('views/custom-blocks'));
```

### 3. Components

Runtime Section/Block instances hydrated from page JSON.

- `Section` — Runtime section object with settings, blocks, and editor attributes
- `Block` — Runtime block object with settings, blocks, and editor attributes
- `Settings` — Settings value object with magic property access

```blade
{{-- In a section Blade file --}}
<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
    @blocks($section)
</section>
```

### 4. Rendering

Blade rendering engine that transforms JSON into HTML.

- `Renderer` — Core rendering engine: hydrates JSON into objects, renders via Blade
- `EditorAttributes` — Generates `data-editor-*` attributes for sections/blocks
- `BladeDirectives` — Registers `@blocks`, `@sections`, `@schema`, `@pbEditorClass`

```php
use PageBuilder\Facades\Page;

// Render a page
$html = Page::render('home');

// Render with extra meta
$html = Page::render('home', ['title' => 'My Home Page']);
```

### 5. Services

High-level services for page management and theme configuration.

- `PageRenderer` — Loads page JSON, renders all enabled sections in order
- `PageStorage` — Reads/writes page JSON files to disk
- `PageRegistry` — Cached page manifest for fast lookups
- `ThemeSettings` — Global theme settings persistence and access

```php
use PageBuilder\Facades\Page;
use PageBuilder\Facades\Theme;

// Get page data
$page = Page::find('home');

// Get theme settings
$settings = Theme::settings();
```

## Data Flow

### Page Rendering Flow

1. **Request** — User visits `/`
2. **Route** — `WebPageController` handles the request
3. **Storage** — `PageStorage` loads `{slug}.json` from disk
4. **Hydration** — `PageData::fromArray()` converts raw array to object
5. **Registry** — For each section/block, look up schema in registry
6. **Defaults** — Merge user settings with schema defaults
7. **Components** — Create `Section` and `Block` objects
8. **Rendering** — Render each section via Blade
9. **Response** — Return rendered HTML

### Editor Flow

1. **Request** — User visits `/?editor=true`
2. **Auth** — Check editor authorization (middleware + callback)
3. **Shell** — Render editor shell (React SPA)
4. **API** — Frontend loads page JSON via API
5. **Edit** — User modifies sections/blocks
6. **Save** — Frontend sends JSON to API
7. **Storage** — `PageStorage` writes JSON to disk
8. **Preview** — Live preview updates in iframe

## Key Classes

| Class             | Layer      | Responsibility                       |
| ----------------- | ---------- | ------------------------------------ |
| `SectionSchema`   | Schema     | Immutable section type definition    |
| `BlockSchema`     | Schema     | Immutable block type definition      |
| `SettingSchema`   | Schema     | Immutable setting definition         |
| `SectionRegistry` | Registry   | Discovers and stores section schemas |
| `BlockRegistry`   | Registry   | Discovers and stores block schemas   |
| `Section`         | Components | Runtime section instance             |
| `Block`           | Components | Runtime block instance               |
| `Settings`        | Components | Settings value object                |
| `Renderer`        | Rendering  | Core rendering engine                |
| `PageRenderer`    | Services   | Full page rendering                  |
| `PageStorage`     | Services   | JSON file read/write                 |
| `ThemeSettings`   | Services   | Theme settings persistence           |

## Facades

```php
use PageBuilder\Facades\Section;
use PageBuilder\Facades\Block;
use PageBuilder\Facades\Page;
use PageBuilder\Facades\Theme;
```

Facades provide a static interface to the underlying services.
