# AI Agent Guidelines — Laravel Page Builder

This file provides AI agents (Cursor, Claude Code, GitHub Copilot, Codex, Gemini CLI) with the conventions, architecture, and patterns needed to work effectively with this package.

Detailed guidelines are in [.ai/guidelines/page-builder/](.ai/guidelines/page-builder/).

---

## Package Identity

- **Package:** `coderstm/laravel-page-builder`
- **Type:** Laravel package — multi-theme, JSON-driven page builder
- **PHP:** 8.2+ | **Laravel:** 11.x / 12.x | **PSR-12**

---

## Architecture (Five Layers)

Dependencies flow **downward only**. Never import from a higher layer.

```
Schema → Registry → Components → Rendering → Services/Controllers
```

| Layer | Directory | Purpose |
|---|---|---|
| Schema | `src/Schema/` | Immutable value objects (SectionSchema, BlockSchema, SettingSchema) |
| Registry | `src/Registry/` | Discovers + stores schemas from Blade `@schema()` directives |
| Components | `src/Components/`, `src/Collections/` | Runtime Section/Block instances hydrated from page JSON |
| Rendering | `src/Rendering/` | Blade rendering engine (Renderer, EditorAttributes, BladeDirectives) |
| Services | `src/Services/` | PageRenderer, PageStorage, PageRegistry, ThemeSettings |

---

## The Page JSON

Every page is a JSON document with sections and an order array:

```json
{
    "sections": {
        "hero": {
            "type": "hero",
            "settings": { "title": "Hello" },
            "blocks": {},
            "order": [],
            "disabled": false
        }
    },
    "order": ["hero"],
    "layout": {
        "type": "page",
        "sections": {
            "header": { "type": "site-header", "settings": {}, "blocks": {}, "order": [] }
        }
    }
}
```

See [layouts.md](.ai/guidelines/page-builder/layouts.md) for the full field reference.

---

## Sections

A section = a Blade file at `resources/views/sections/{type}.blade.php` with a `@schema()` directive.

```blade
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome'],
    ],
])

<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
    @blocks($section)
</section>
```

See [sections.md](.ai/guidelines/page-builder/sections.md) for setting types and full examples.

---

## Blocks

A block = a Blade file at `resources/views/blocks/{type}.blade.php` with a `@schema()` directive.

```blade
@schema([
    'name' => 'Card',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => ''],
    ],
])

<div {!! $block->editorAttributes() !!}>
    <h3>{{ $block->settings->title }}</h3>
</div>
```

Container blocks use `@blocks($block)` to render their children.

See [blocks.md](.ai/guidelines/page-builder/blocks.md) for nesting, theme vs local blocks, and full examples.

---

## Themes

Theme files shadow the built-in package files. Last registration wins.

```
themes/my-theme/views/
├── layouts/app.blade.php     # Master layout with @sections('header'), @yield('content'), @sections('footer')
├── sections/                 # Theme-specific sections
└── blocks/                   # Theme-specific blocks
```

See [themes.md](.ai/guidelines/page-builder/themes.md) for the full theme directory structure and examples.

---

## Mandatory Code Rules

1. `declare(strict_types=1)` on every PHP file.
2. All properties explicitly typed; all methods have return types.
3. Use `readonly` for value objects and DTOs.
4. Always use constructor injection — never `new ClassName()`.
5. Never mutate a Schema object after construction.
6. All rendering goes through `Renderer` — never render views directly.
7. Use `@blocks($section|$block)` in Blade — never call the renderer from a view.

---

## Facades

```php
use Coderstm\PageBuilder\Facades\Section;
use Coderstm\PageBuilder\Facades\Block;
use Coderstm\PageBuilder\Facades\Page;
use Coderstm\PageBuilder\Facades\Theme;
```

---

## Blade Directives

```blade
@schema([...])         {{-- schema definition — no-op at render time --}}
@blocks($section)      {{-- render section's top-level blocks --}}
@blocks($block)        {{-- render block's child blocks --}}
@sections('header')    {{-- render a layout slot (header/footer) --}}
@pbEditorClass         {{-- adds editor class to <html> in editor mode --}}
```

---

## Full Guidelines

| Topic | File |
|---|---|
| Architecture, config, routes | [core.md](.ai/guidelines/page-builder/core.md) |
| Page JSON structure | [layouts.md](.ai/guidelines/page-builder/layouts.md) |
| Sections, settings, schema | [sections.md](.ai/guidelines/page-builder/sections.md) |
| Blocks, nesting, containers | [blocks.md](.ai/guidelines/page-builder/blocks.md) |
| Themes, master layout, assets | [themes.md](.ai/guidelines/page-builder/themes.md) |
