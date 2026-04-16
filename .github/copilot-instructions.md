# AI Development Instructions (STRICT)

## Role

You are a **senior Laravel package developer** responsible for building and maintaining a **Laravel Page Builder package**.

All generated code must follow **Laravel framework conventions, PSR standards, and strict package architecture**.

---

## Core Principles (MANDATORY)

1. **Strict Laravel 12.x conventions**.
2. **PSR-12 Compliance**.
3. **Layered Architecture**: Five clearly separated layers — Schema, Registry, Components (Runtime), Rendering, and Editor.
4. **Strict Typing**:
   - All properties MUST be typed.
   - All methods MUST have return types.
   - Use `readonly` for value objects, DTOs, and immutable data.
   - Use PHP 8.2+ features (readonly properties, enums, named arguments).

---

## Package Structure Requirements

Always adhere to this hierarchy:

```
packages/page-builder/
│
├── src/
│   ├── Schema/              # Layer 1: DEFINITIONS — immutable value objects describing structure
│   │   ├── SectionSchema.php    # Defines section structure (settings, blocks, presets, limits)
│   │   ├── BlockSchema.php      # Defines block structure (type, name, settings)
│   │   └── SettingSchema.php    # Defines a single setting (id, type, label, default)
│   │
│   ├── Registry/            # Layer 2: REGISTRATION — discovers and stores available schemas
│   │   ├── SectionRegistry.php  # Scans Blade files, registers & provides SectionSchema objects
│   │   ├── BlockRegistry.php    # Registers global/theme-level block schemas
│   │   └── SchemaExtractor.php  # Parses @schema() directives from Blade files
│   │
│   ├── Components/          # Layer 3: RUNTIME — page JSON data hydrated into typed objects
│   │   ├── Section.php          # Runtime section instance (id, type, settings, blocks)
│   │   ├── Block.php            # Runtime block instance (id, type, settings, blocks)
│   │   └── Settings.php         # Key→value settings bag with schema-aware defaults
│   │
│   ├── Collections/         # Layer 3b: COLLECTIONS — ordered collections of runtime objects
│   │   ├── BlockCollection.php      # Ordered collection of Block instances
│   │   └── SectionCollection.php    # Ordered collection of Section instances with render()
│   │
│   ├── Rendering/           # Layer 4: RENDERING — Blade rendering pipeline
│   │   ├── Renderer.php         # Core engine: hydrates JSON → objects, renders via Blade
│   │   ├── EditorAttributes.php # Generates data-editor-* attributes for sections/blocks
│   │   └── BladeDirectives.php  # Registers @blocks, @schema, @pbEditorClass
│   │
│   ├── Services/            # High-level orchestrators for pages
│   │   ├── PageRenderer.php     # Loads page JSON, renders all sections, publishes to Blade
│   │   ├── PageRegistry.php     # Cached page manifest (bootstrap/cache/pagebuilder_pages.php)
│   │   └── PageService.php      # Route-level page service
│   │
│   ├── Support/             # Internal support classes
│   │   └── PageData.php     # Page JSON value object (sections, order, title, meta)
│   │
│   ├── Facades/             # Clean Facade proxies
│   │   ├── Section.php          # Facade for SectionRegistry
│   │   └── Page.php             # Facade for PageService
│   │
│   ├── Http/                # Controllers & Middleware
│   │   ├── Controllers/
│   │   └── Middleware/
│   │
│   ├── Commands/            # Artisan commands
│   ├── Models/              # Eloquent models
│   ├── Providers/           # Service Provider
│   ├── PageBuilder.php      # Main API entry point (editor mode, scripts, config)
│   └── helpers.php          # Global helper functions (pb_editor())
│
└── resources/
    └── views/
        ├── blocks/              # Theme-level block Blade views (reusable across sections)
        │   ├── row.blade.php        # Responsive CSS grid row — wraps column blocks
        │   └── column.blade.php     # Flex column — accepts any nested theme block
        └── sections/            # Section Blade views registered via SectionRegistry
            └── section.blade.php    # Generic configurable section container
```

> **Layout feature**: The `Page` JSON may also contain a `layout` key (see [Page JSON — Layout Sections](#page-json--layout-sections)) holding per-page overrides for structural slots (header, footer) rendered by the Blade layout file. These are surfaced in the editor as fixed `LayoutSectionRow` entries above and below the sortable page section list.

---

## Layer Responsibilities

### Schema Layer (`Schema/`)

- **Purpose**: Immutable definitions of what a section/block/setting looks like
- **Pattern**: Value objects with readonly properties, constructed from raw arrays
- **Rule**: Schema objects are NEVER mutated after construction
- **Examples**: `SectionSchema`, `BlockSchema`, `SettingSchema`

### Registry Layer (`Registry/`)

- **Purpose**: Discovers, stores, and provides typed schema objects
- **Pattern**: Singleton services, lazy-loaded from Blade file scanning
- **Rule**: `SectionRegistry` is the single source of truth for available section types
- **Examples**: `SectionRegistry::get('hero')` returns a `SectionSchema`

### Components Layer (`Components/` + `Collections/`)

- **Purpose**: Runtime instances hydrated from page JSON data using schema defaults
- **Pattern**: Readonly value objects created by `Renderer::hydrateSection()`
- **Rule**: Components use `Settings` for typed access to settings with default resolution
- **Examples**: `Section`, `Block`, `Settings`, `BlockCollection`, `SectionCollection`

#### `Block` — nested blocks support

`Block` carries a `readonly BlockCollection $blocks` property that holds its own child blocks (e.g. columns inside a row). This is hydrated **recursively** by `Renderer::hydrateBlocks()`. An empty `BlockCollection` is always present — leaf blocks simply have an empty collection.

```php
// In a block Blade view:
$block->blocks->count();          // number of child blocks
$block->blocks->ofType('column'); // filter children by type
```

### Rendering Layer (`Rendering/`)

- **Purpose**: Converts runtime objects into HTML via Blade views
- **Pattern**: `Renderer` is the central rendering engine, injected as a singleton
- **Rule**: ALL rendering goes through `Renderer` — never render sections directly
- **Key methods**:
  - `renderSection(Section)` — renders a section view; auto-injects live-text attributes in editor mode
  - `renderBlocks(Section)` — renders all top-level blocks of a section (used by `@blocks($section)`)
  - `renderBlock(Block, ?Section)` — renders a single block view, passing `$section` as context
  - `renderBlockChildren(Block, ?Section)` — renders all child blocks of a container block (used by `@blocks($block)`)
  - `hydrateSection(string, array, bool)` — hydrates raw JSON → `Section` with nested `Block` tree
  - `renderRawSection(string, array, bool)` — convenience method for API preview calls

### Editor Layer (`Editor/`)

- **Purpose**: Optional — generates data-attributes when editor mode is active
- **Pattern**: Static utility methods that return empty strings when editor is off
- **Rule**: Editor attributes are ONLY generated when `PageBuilder::editor()` is true
- **Examples**: `EditorAttributes::forSection()`, `EditorAttributes::autoInjectLiveText()`

---

## Dependency Flow

```
Schema → Registry → Renderer → Services/Controllers
                  ↗
         Components (hydrated by Renderer)
                  ↗
         Editor (optional, used by Renderer)
```

**Dependencies flow downward. Lower layers NEVER depend on higher layers.**

---

## Blade & @schema Rules

Both **section** and **block** Blade files must contain a `@schema` directive. The `@schema` array is extracted at registration time by `SchemaExtractor` and is a no-op at render time.

### Section Blade template

Sections live in the configured sections path (e.g. `resources/views/sections/`) and are registered via `SectionRegistry`.

```blade
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome'],
        ['id' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle', 'default' => ''],
    ],
    'blocks' => [
        ['type' => 'row',    'name' => 'Row'],
        ['type' => '@theme'],        // accepts any registered theme block
    ],
    'presets' => [
        ['name' => 'Hero'],
    ],
])

<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
    @blocks($section)   {{-- renders all top-level blocks --}}
</section>
```

### Block Blade template

Block Blade files live in `resources/views/blocks/` and are resolved by name (`blocks.{type}`). Container blocks (e.g. `row`, `column`) use `@blocks($block)` to render their own nested children.

```blade
@schema([
    'name'     => 'Row',
    'settings' => [
        ['id' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '2',
         'options' => [['value' => '2', 'label' => '2 Columns'], ...]],
    ],
    'blocks'  => [
        ['type' => 'column', 'name' => 'Column'],
    ],
    'presets' => [
        ['name' => 'Two Columns', 'settings' => ['columns' => '2'],
         'blocks' => [['type' => 'column'], ['type' => 'column']]],
    ],
])

<div {!! $block->editorAttributes() !!} class="grid grid-cols-2 gap-6">
    @blocks($block)   {{-- renders child column blocks --}}
</div>
```

### `@blocks()` directive — dual context

`@blocks()` accepts **either** a `Section` or a `Block` object:

| Expression          | Calls                                            | Use case                                            |
| ------------------- | ------------------------------------------------ | --------------------------------------------------- |
| `@blocks($section)` | `Renderer::renderBlocks(Section)`                | Render all top-level blocks of a section            |
| `@blocks($block)`   | `Renderer::renderBlockChildren(Block, ?Section)` | Render nested child blocks inside a container block |

> **Rule**: Never call the renderer directly from a Blade view. Always use `@blocks()`.

### Block Detection: Local vs Theme-block Reference

In Blade `@schema` directives, a `blocks` array entry can be either a **local block definition** or a **theme-block reference**. Detection is based on presence of extra keys:

| Entry                                                 | Type                  | Detection                                 | Resolution                                                                                 |
| ----------------------------------------------------- | --------------------- | ----------------------------------------- | ------------------------------------------------------------------------------------------ |
| `{ type: 'column' }`                                  | Theme-block reference | Only has `type` key (bare)                | Full schema from `themeBlocks` registry, including its own child-slot definition           |
| `{ type: '@theme' }`                                  | @theme wildcard       | Only has `type` key (bare)                | Any registered theme block is allowed                                                      |
| `{ type: 'column', name: 'Column', settings: [...] }` | Local definition      | Has extra keys (`name`, `settings`, etc.) | Used as-is; its own `blocks` key (if present) governs child-slot; no theme-registry lookup |

**Key principle**:

- **Bare references** (`{ type: 'foo' }`) are theme-block lookups — resolve full schema from registry.
- **Local definitions** (has `name`, `settings`, or other keys) are authoritative — used as-is without theme merging.
- This allows `section.blade.php` to declare `blocks: [{ type: 'row' }]` without repeating `row`'s internal child-slot definition. The bare `row` reference is resolved from the theme registry, which includes `row`'s own `blocks: [{ type: 'column' }]`, so "Add block → Column" correctly appears inside Row.
- Conversely, `multi-columns.blade.php` can define `blocks: [{ type: 'column', name: 'Column', settings: [...] }]` as a local leaf block with no `blocks` key, so no "Add block" appears inside columns.

### Section Template API

- `$section->id` — unique instance ID
- `$section->type` — section type identifier
- `$section->settings->key` — typed setting access with defaults
- `$section->blocks` — `BlockCollection` of hydrated top-level blocks
- `$section->editorAttributes()` — editor `data-*` attributes (empty string when not in editor mode)
- `@blocks($section)` — renders all top-level blocks

### Block Template API

- `$block->id` — unique block instance ID
- `$block->type` — block type identifier (e.g. `row`, `column`)
- `$block->settings->key` — typed setting access with defaults
- `$block->blocks` — `BlockCollection` of nested child blocks (populated for container blocks)
- `$block->editorAttributes()` — editor `data-*` attributes (empty string when not in editor mode)
- `$section` — parent `Section` object, always available in block views (may be `null` for isolated renders)
- `@blocks($block)` — renders all child blocks of this block

---

## Built-in Blade Views

The package ships with ready-to-use Tailwind CSS block and section views under `resources/views/`.

### `sections/section.blade.php` — Generic Section Container

A fully configurable section that wraps any blocks inside a max-width container.

**Settings**:

| id                           | type         | default   | description                                      |
| ---------------------------- | ------------ | --------- | ------------------------------------------------ |
| `anchor`                     | text         | `''`      | HTML `id` attribute for anchor links             |
| `padding_top`                | select       | `md`      | none / xs / sm / md / lg / xl / 2xl              |
| `padding_bottom`             | select       | `md`      | none / xs / sm / md / lg / xl / 2xl              |
| `max_width`                  | select       | `7xl`     | full / sm / md / lg / xl / 2xl / 5xl / 6xl / 7xl |
| `background_color`           | color        | `''`      | CSS background colour                            |
| `background_image`           | image_picker | `''`      | Background image URL                             |
| `background_overlay_opacity` | range        | `0`       | 0–100 % dark overlay over the background image   |
| `color_scheme`               | select       | `default` | default / light / dark / primary / accent        |

**Accepted blocks**: `row`, `@theme`

**Presets**: _Section_, _Section with Two-Column Row_

### `blocks/row.blade.php` — Responsive Grid Row

A CSS grid container that wraps `column` blocks. Resolves column count and gap into Tailwind classes.

**Settings**:

| id                   | type     | default | description                                            |
| -------------------- | -------- | ------- | ------------------------------------------------------ |
| `columns`            | select   | `2`     | 1 – 6 columns                                          |
| `gap`                | select   | `md`    | none / xs / sm / md / lg / xl                          |
| `vertical_alignment` | select   | `start` | start / center / end / stretch                         |
| `reverse_on_mobile`  | checkbox | `false` | Reverses column order on mobile via `flex-col-reverse` |
| `full_width`         | checkbox | `false` | Reserved for future full-bleed override                |

**Accepted blocks**: `column`

**Presets**: _Two Columns_, _Three Columns_, _Four Columns_

### `blocks/column.blade.php` — Flex Column

A flex column that can hold any theme blocks as children. Supports individual width overrides, alignment, padding, and background.

**Settings**:

| id                     | type         | default | description                                    |
| ---------------------- | ------------ | ------- | ---------------------------------------------- |
| `width`                | select       | `auto`  | auto (equal-width) or col-span-1 to col-span-6 |
| `horizontal_alignment` | select       | `start` | start / center / end                           |
| `vertical_alignment`   | select       | `start` | start / center / end / between                 |
| `padding`              | select       | `none`  | none / sm / md / lg / xl                       |
| `background_color`     | color        | `''`    | CSS background colour                          |
| `background_image`     | image_picker | `''`    | Background image URL                           |
| `hide_on_mobile`       | checkbox     | `false` | Hidden below `sm` breakpoint                   |
| `hide_on_desktop`      | checkbox     | `false` | Hidden above `sm` breakpoint                   |

**Accepted blocks**: `@theme` (any registered theme block)

**Presets**: _Column_

### Page JSON structure (Section → Row → Column)

```json
{
  "sections": {
    "hero": {
      "type": "section",
      "settings": { "padding_top": "lg", "max_width": "7xl" },
      "blocks": {
        "row1": {
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
      "order": ["row1"]
    }
  },
  "order": ["hero"]
}
```

### Page JSON — Layout Sections

In addition to `sections` and `order`, a page JSON may contain a `layout` key. This holds per-page overrides for structural slots that live **outside** the `@yield` block in the Blade layout (e.g. a header or footer section registered via `@sections()`).

Layout sections are **not sortable** — their position is determined by the Blade layout file. In the editor they appear as fixed `LayoutSectionRow` entries above and below the sortable page section list.

```json
{
    "sections": { ... },
    "order": ["hero", "cta"],
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

**Rules**:

- `layout.sections` is keyed by **position slug** (`"header"`, `"footer"`, etc.), not a random ID.
- Keys that match `"header"` or carry `position: "top"` render in the top zone; everything else falls to the bottom zone.
- `disabled: true` causes `@sections()` to return an empty string for that slot.
- `_name` is supported on layout section instances (same as page sections) and overrides the schema display name in the editor.
- Layout section settings are edited through the same `SettingsPanel` as page sections — `SettingsPanel` resolves the instance from `currentPage.layout.sections[id]` when it is not found in `currentPage.sections[id]`.

---

## Dependency Injection & Instantiation

```php
// WRONG
$renderer = new Renderer();

// CORRECT
public function __construct(
    private readonly Renderer $renderer,
) {}
```

---

## Code Style Enforcement

```php
declare(strict_types=1);

namespace Coderstm\PageBuilder\Schema;

use Illuminate\Contracts\Support\Arrayable;

final class SectionSchema implements Arrayable
{
    public readonly string $name;
    public readonly string $tag;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? '';
        $this->tag  = $data['tag'] ?? 'section';
    }

    public function toArray(): array
    {
        return ['name' => $this->name, 'tag' => $this->tag];
    }
}
```

---

## React SPA Frontend Architecture (Editor UI)

The Page Builder interface is a React Single Page Application (SPA) driven by Vite and embedded inside the Laravel backend. It follows a **class-based architecture** with strict **SOLID** principles.

### Core Philosophy

1. **Central `Editor` class**: A single `Editor` instance (created in `main.tsx`, provided via `EditorProvider`) owns all managers and services. Any component accesses it via `useEditorInstance()` — no prop drilling.
2. **Zero prop drilling**: Every component reads its own state directly from the editor context, manager hooks (`useEditorLayout`, `useEditorNavigation`), and the Zustand store (`useStore`). `App.tsx` passes zero props to any child.
3. **Class-based managers**: All business logic lives in manager classes (`SectionManager`, `BlockManager`, `LayoutManager`, etc.). React is a rendering layer only.
4. **Zustand + Immer for State**: All page, section, and block data mutations happen in a centralized `useStore.ts` layer. Components NEVER mutate state directly; they call actions on the store or manager methods.
5. **Iframe Message Bus**: The React editor UI and the Blade-rendered preview iframe communicate strictly via a `MessageBus` abstraction (`IframeAdapter`). The UI never touches the iframe DOM directly.
6. **Dynamic Field Registry**: Setting inputs are resolved dynamically via `FieldRegistry`. New setting types are added without modifying core engine files.

### Editor Directory Structure

```text
resources/js/
├── App.tsx                  # Root editor component — pure layout composition, zero props passed
├── main.tsx                 # Entry point, creates Editor instance, mounts EditorProvider
├── core/
│   ├── editor/              # Class-based managers
│   │   ├── Editor.ts            # Central editor class — all managers, undo/redo, addBlockFromPreview
│   │   ├── EventBus.ts          # Typed pub/sub event emitter
│   │   ├── SectionManager.ts    # Section CRUD + events
│   │   ├── BlockManager.ts      # Block CRUD + schema resolution + events
│   │   ├── SelectionManager.ts  # Selection state + router adapter
│   │   ├── LayoutManager.ts     # Editor UI state (sidebar, modals, device)
│   │   ├── NavigationManager.ts # URL routing + state sync
│   │   ├── BootstrapManager.ts  # Startup + page-switch orchestration
│   │   ├── PageManager.ts       # Page load/save + meta/theme
│   │   ├── HistoryManager.ts    # Undo/redo with snapshots
│   │   ├── PreviewManager.ts    # iframe communication via MessageBus
│   │   ├── InteractionManager.ts# iframe interaction helpers (hover, focus)
│   │   ├── ConfigManager.ts     # Runtime config access
│   │   └── ShortcutManager.ts   # Keyboard shortcuts
│   ├── editorContext.tsx    # EditorProvider + useEditorInstance() hook
│   ├── registry/            # [OCP] Field & Section registries (FieldRegistry.ts)
│   ├── messaging/           # [DIP] Iframe communication abstraction (MessageBus.ts, IframeAdapter.ts)
│   └── store/               # [SRP/ISP] State management (Zustand + Immer useStore.ts)
│       └── slices/
│           ├── pageSlice.ts         # pages, currentPage, pageMeta, themeSettings
│           ├── selectionSlice.ts    # selectedSection, selectedBlock, selectedBlockPath
│           ├── sectionSlice.ts      # Section CRUD actions
│           └── blockSlice.ts        # Block CRUD actions (nested support via parentPath)
├── hooks/
│   ├── useEditor.ts         # Root side-effects only: bootstrap, history snapshots, shortcuts
│   ├── useEditorLayout.ts   # React adapter for LayoutManager (useSyncExternalStore)
│   └── useEditorNavigation.ts # React Router bridge for NavigationManager
├── components/              # UI components — all self-contained, zero props from parent
│   ├── EditorHeader.tsx         # Header — reads from hooks/store directly
│   ├── EditorSidebar.tsx        # Left sidebar — reads from hooks/store directly
│   ├── SettingsSidebar.tsx      # Right sidebar (dual-sidebar) — reads loading from store
│   ├── PreviewCanvas.tsx        # iframe — owns iframeRef, wires messageBus on mount
│   ├── AddSectionModal.tsx      # Section picker — reads from layout/nav/store directly
│   ├── PageMetaPanel.tsx        # Page meta — reads pageMeta from store
│   ├── ThemeSettingsPanel.tsx   # Theme settings — reads themeSettings from store
│   ├── SettingsPanel.tsx        # Section/block settings form
│   ├── LayoutPanel.tsx          # Section/block tree
│   ├── settings/            # Setting field components
│   └── layout/
│       ├── SortableSectionRow.tsx   # Draggable page section row
│       └── LayoutSectionRow.tsx     # Fixed non-sortable layout slot row (header/footer)
├── services/                # [SRP] API & Network logic (api.ts)
└── types/                   # Shared TypeScript definitions
    └── page-builder.ts      # Page, SectionInstance, LayoutSectionInstance, PageLayout …
```

### Asset Provider System

The editor uses a **provider pattern** to decouple asset management (media library, file uploads) from the core editor. This allows teams to swap the default Laravel backend for S3, Cloudinary, R2, or any custom storage — without touching core editor code.

#### Architecture

```
types/asset.ts          — AssetProvider interface + Asset / AssetList types
config.tsx              — EditorConfig { assets: { provider: AssetProvider } }
                          defaultConfig uses laravelAssetProvider
services/
  laravelAssetProvider.ts   — Default provider (Laravel disk, /api/pagebuilder/assets)
  assetService.ts           — Thin wrapper; components always go through AssetService
core/editor.ts          — createEditor() wires EditorConfig.assets.provider → AssetService
main.tsx                — PageBuilder.init() accepts assets.provider and passes it through
```

#### `AssetProvider` contract (`types/asset.ts`)

```typescript
export interface Asset {
  id: string | number;
  name: string;
  url: string; // publicly accessible — stored in page JSON
  thumbnail?: string;
  size?: number;
  type?: string;
}

export interface AssetList {
  data: Asset[];
  pagination: { page: number; per_page: number; total: number };
}

export interface AssetProvider {
  list(params?: { page?: number; search?: string }): Promise<AssetList>;
  upload(file: File): Promise<Asset>;
}
```

#### Registering a custom provider

Pass the provider via `PageBuilder.init()` on the host page:

```html
<script src="/vendor/pagebuilder/app.js"></script>
<script>
  const s3Provider = {
    async list({ page = 1, search = "" } = {}) {
      const q = new URLSearchParams({ page, q: search });
      const res = await fetch(`/api/pagebuilder/assets?${q}`);
      return res.json(); // { data: Asset[], pagination: { ... } }
    },
    async upload(file) {
      const body = new FormData();
      body.append("file", file);
      const res = await fetch("/api/pagebuilder/assets/upload", {
        method: "POST",
        body,
      });
      return res.json(); // Asset
    },
  };

  PageBuilder.init({
    baseUrl: "/pagebuilder",
    assets: { provider: s3Provider },
  });
</script>
```

#### Rules

- **UI components NEVER reference a specific provider** — they always call through `AssetService`.
- `laravelAssetProvider` reads `config.baseUrl` **lazily inside each method** (not at module top-level) to avoid a circular dependency between `config.tsx` and `services/laravelAssetProvider.ts`.
- The `url` stored in page JSON must be a publicly accessible URL (not a signed/temporary link).
- Full provider examples (S3, Cloudinary, R2) are in `docs/developer-docs.md#custom-asset-providers` and `README.md#custom-asset-providers`.

---

### Key Mechanisms

- **Store Hydration**: The `window.PageBuilder.init()` script passes initial Laravel `config` containing pages, sections, blocks, and schemas into React, which hydrates `useStore`.
- **Event→Preview Wiring in `Editor` constructor**: `Editor._wirePreviewEvents()` subscribes to all `section:*` / `block:*` events at construction time and forwards them to `PreviewManager`. No React `useEffect` is needed — any mutation anywhere automatically syncs to the preview.
- **Live-text vs Re-render split**: A `section:settings-changed` or `block:settings-changed` event with a **single string value** triggers only `updateLiveText()` (instant DOM patch, zero server round-trip). Multi-value or non-string changes trigger `debouncedRerender()`. This rule is enforced inside `Editor._wirePreviewEvents()` with an early `return`.
- **Injected Editor Scripts**: `PreviewCanvas` automatically injects `EditorScriptInjector.ts` (CSS and JS) into the Blade iframe on load. This script disables pointer events, forces default cursors, and intercepts clicks to send `section-selected` / `block-selected` / `add-section` / `add-block` messages via `postMessage`.
- **Bi-directional Focus**: Clicking an element with `data-live-text-setting` inside the iframe posts a message causing the React `SettingsPanel` to auto-scroll and focus the corresponding native `input` or `contenteditable` component.
- **Canvas-Only Visibility Toggles**: Toggling the eye icon on a section or block row **never** triggers a server re-render. `editor.sections.toggleDisabled(id)` / `editor.blocks.toggleDisabled(...)` mutate the Zustand store and emit `section:toggled` / `block:toggled`. `Editor._wirePreviewEvents()` handles those events and calls `preview.toggleVisibility()`, which sends a `toggle-visibility` postMessage. `EditorScriptInjector.ts` receives it and toggles `pb-disabled-section` / `pb-disabled-block` CSS classes via `classList.toggle()` — synchronous and zero-latency.
- **`PreviewCanvas` owns the iframe**: `PreviewCanvas` creates its own `iframeRef`, creates the `messageBus` via `editor.interaction.createMessageBus(iframeRef)`, and wires it into `editor.preview.setMessageBus()` on mount. The component reads all state from hooks and calls `editor.*` methods directly — no props.

#### Canvas Visibility — Message Contract

```typescript
// Sent by: Editor._wirePreviewEvents() → preview.toggleVisibility()
// Handled by: EditorScriptInjector.ts (injected iframe JS)
{
    type: "toggle-visibility",
    kind: "section" | "block",  // what is being toggled
    sectionId: string,           // owning section ID (always required)
    blockId: string | null,      // block ID (required when kind === "block", else null)
    disabled: boolean,           // the NEW disabled value (post-toggle)
}
```

**Rules**:

- Visibility toggles MUST go through `editor.sections.toggleDisabled()` / `editor.blocks.toggleDisabled()` — never call `preview.debouncedRerender()` for a toggle.
- `newDisabled` is computed as `!currentPage?.sections?.[sectionId]?.disabled` (inverse of pre-toggle state) because Immer hasn't flushed a new reference by the time the callback reads `currentPage`.
- The `toggle-visibility` handler in `EditorScriptInjector.ts` is the single source of truth for applying `pb-disabled-section` / `pb-disabled-block` — do not add duplicate class-toggle logic elsewhere in the injector.

### Layout Sections — Dual Selection Sources

The editor tracks two distinct selection sources that must never conflict:

| Source         | State location                        | Set by                                | Cleared by                             |
| -------------- | ------------------------------------- | ------------------------------------- | -------------------------------------- |
| Page section   | URL search param `?section=id`        | `navigation.setSection(id)`           | `navigation.clearSelection()`          |
| Layout section | Zustand store `selectedLayoutSection` | `store.setSelectedLayoutSection(key)` | `store.setSelectedLayoutSection(null)` |

**Rules** (enforced inside `LayoutPanel` and `SettingsPanel`, accessed via `useEditorInstance()`):

1. Selecting a layout section always calls `editor.navigation.clearSelection()` before setting the store, preventing a stale URL param from shadowing the layout selection.
2. `showSettings` is `!!(selectedSection || selectedLayoutSection)` — true for either kind of selection.
3. `SettingsPanel` resolves the selected instance via a two-step lookup (see below).

**`SettingsPanel` lookup chain** (for a given `id`):

```
currentPage.sections[id]                // page section — fast path
  ?? currentPage.layout?.sections[id]   // layout section — fallback
  ?? null                               // nothing selected
```

#### `LayoutSectionRow` component ([components/layout/LayoutSectionRow.tsx](resources/js/components/layout/LayoutSectionRow.tsx))

A non-sortable, non-duplicable, non-removable fixed row for layout slots (header/footer). Key differences from `SortableSectionRow`:

- No drag handle.
- No duplicate / remove buttons — layout slots are structural.
- Uses `PanelTop` icon for `position="top"` slots, `PanelBottom` for `position="bottom"`.
- Highlights with `text-blue-600` (label) and `text-blue-500` (icon) when `isSelected`.
- Debounced hover using the same 60 ms timer pattern as sortable rows.
- Visibility toggle (Eye/EyeOff) always visible when disabled; shown on hover otherwise.

#### Store actions for layout sections

| Action                                  | Slice            | Description                                                             |
| --------------------------------------- | ---------------- | ----------------------------------------------------------------------- |
| `setSelectedLayoutSection(key \| null)` | `selectionSlice` | Sets store layout selection; clears `selectedSection` + `selectedBlock` |
| `setSelectedSection(id \| null)`        | `selectionSlice` | Sets page section; clears `selectedLayoutSection`                       |
| `toggleLayoutSectionDisabled(key)`      | `sectionSlice`   | Flips `currentPage.layout.sections[key].disabled`                       |

#### TypeScript types (`types/page-builder.ts`)

```typescript
export interface PageLayout {
  type?: string; // e.g. "page"
  sections: Record<string, LayoutSectionInstance>; // keyed by position slug
}

export interface LayoutSectionInstance {
  type: string; // section schema type (e.g. "site-header")
  _name?: string; // custom label overriding schema name
  settings: Record<string, any>;
  blocks: Record<string, any>;
  order: string[];
  disabled?: boolean; // when true @sections() returns '' for this slot
}
```

#### LayoutPanel — top/bottom zone rendering

`LayoutPanel` partitions `currentPage.layout?.sections` into two zones:

- **Top zone** (`position === "top"` or key `=== "header"`) — rendered above the sortable page-section list with a "Layout" sub-label.
- **Bottom zone** (`position === "bottom"` or key `=== "footer"`, plus any unassigned keys) — rendered below the sortable list.

Each zone maps its keys to `<LayoutSectionRow>` components. `LayoutPanel` reads `selectedLayoutSection` from the Zustand store and calls `store.setSelectedLayoutSection(key)` directly — no props passed down.

### LayoutPanel — Block Tree Resolution

The **React LayoutPanel component** (`resources/js/components/LayoutPanel.tsx`) dynamically renders a hierarchical tree of sections and blocks. It uses three utility functions to detect and resolve block types:

#### `isLocalBlockEntry(entry)`

Returns `true` if the entry has keys beyond `type` (e.g. `name`, `settings`, `blocks`). Used to distinguish:

- **Local definition**: `{ type: 'column', name: 'Column', settings: [...] }` → `true`
- **Theme-block reference**: `{ type: 'column' }` → `false`

#### `resolveBlockSchema(blockType, parentRawBlocks, themeBlocks)`

Resolves the full schema for a block instance:

1. If parent is `@theme` mode (`{ type: '@theme' }`), return full schema from `themeBlocks` registry.
2. Find the matching entry in parent's `blocks` array:
   - **Local definition** (has extra keys) → return as-is; its own `blocks` key governs child-slot.
   - **Bare theme-block reference** (only `type`) → resolve full schema from `themeBlocks` registry (includes its child-slot).
3. **Fallback**: if no match found, try `themeBlocks` registry.

#### `getAddableBlockTypes(parentRawBlocks, themeBlocks)`

Returns addable block schemas for a parent's "Add block" picker:

- **@theme mode** → all registered theme blocks
- **local/bare entries** → each entry resolved to full schema (local entries as-is, bare refs from registry)
- **empty** → `[]`

#### `canShowAddBlock(parentRawBlocks)`

Returns `true` if "Add block" button should display. True for any non-empty `blocks` array (regardless of mode).

**Example**: `section.blade.php` with `blocks: [{ type: 'row' }]`

- `row` entry is bare → `resolveBlockSchema` returns `themeBlocks['row'].schema` (full schema including `blocks: [{ type: 'column' }]`)
- Inside Row, the "Add block" picker shows column as an option
- `column` entry inside `row`'s schema is also bare → resolved from theme registry (leaf, no child-slot)
- "Add block" does NOT appear inside columns ✓

---

## Goal

The package must feel like a native Laravel core component (`Cache`, `Gate`, `DB`).

---

## Priority

1. **Architecture Integrity** — class-based `Editor` + managers; no prop drilling; `Editor._wirePreviewEvents()` owns all preview sync.
2. **Strict Typing** — TypeScript on frontend (all managers typed), PHP 8.2+ on backend.
3. **Developer Experience** — clear layer boundaries; any component can call `useEditorInstance()` and access the full editor API.
4. **Performance** — live-text updates skip server re-render; debounced re-renders coalesce rapid changes; zero-latency visibility toggles via postMessage.
